<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Config;

use Exception;
use Monolog\Logger;
use Ratepay\RpayPayments\Components\PaymentHandler\InstallmentPaymentHandler;
use Ratepay\RpayPayments\Components\PaymentHandler\InstallmentZeroPercentPaymentHandler;
use Ratepay\RpayPayments\Components\ProfileConfig\Model\ProfileConfigEntity;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Request\ProfileRequestService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;

class WriterService
{
    /** @var ModelManager */
    protected $modelManager;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ProfileRequestService
     */
    protected $profileRequestService;
    private $db;

    public function __construct(
        ModelManager $modelManager,
        ProfileRequestService $profileRequestService,
        Logger $logger
    )
    {
        $this->db = $modelManager->getConnection();
        $this->modelManager = $modelManager;
        $this->profileRequestService = $profileRequestService;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function truncateConfigTables()
    {
        $schemaManager = $this->modelManager->getConnection()->getSchemaManager();

        $tables = [
            $this->modelManager->getClassMetadata(ConfigInstallment::class)->getTableName(),
            $this->modelManager->getClassMetadata(ProfileConfig::class)->getTableName(),
            $this->modelManager->getClassMetadata(ConfigPayment::class)->getTableName()
        ];
        try {
            $this->db->query("SET FOREIGN_KEY_CHECKS=0");
            foreach ($tables as $table) {
                if ($schemaManager->tablesExist([$table])) {
                    $this->db->query('TRUNCATE TABLE `' . $table . '`;');
                }
            };
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage());
            return false;
        }
        return true;
    }

    private function resetProfileInformation(ProfileConfig $profileConfig)
    {
        // truncate existing payment configurations
        $this->deletePaymentConfigs([$profileConfig->getId()]);
        $profileConfig->setActive(false);
        $profileConfig->setCurrencies([]);
        $profileConfig->setCountryCodesBilling([]);
        $profileConfig->setCountryCodesDelivery([]);
        $profileConfig->setErrorDefault(null);
        $this->modelManager->flush($profileConfig);
    }

    /**
     * Sends a Profile_request and saves the data into the Database
     *
     * @param ProfileConfig $profileConfig
     * @return bool
     */
    public function writeRatepayConfig(ProfileConfig $profileConfig)
    {

        $this->resetProfileInformation($profileConfig);

        // fetch new profile information
        try {
            $this->profileRequestService->setProfileConfig($profileConfig);
            $response = $this->profileRequestService->doRequest();
            $profileConfig = $this->profileRequestService->getProfileConfig();

            if ($response->isSuccessful() === false) {
                $this->logger->error(
                    'Ratepay: Profile_Request failed for profileId ' . $profileConfig->getProfileId()
                );
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Ratepay: Profile_Request failed for profileId ' . $profileConfig->getProfileId()
            );
            return false;
        }
        $responseResult = $response->getResult();
        if (!is_array($responseResult) || $response === false) {
            $this->logger
                ->notice('Ratepay: Profile_Request for profileId ' . $profileConfig->getProfileId() . ' was empty ');
            return false;
        }

        if ($response->isSuccessful() === false) {
            $profileConfig->setActive(false);
//            $profileConfig->setMessage($response->getReasonMessage());
        } elseif (((int)$responseResult['merchantConfig']['merchant-status']) === 1) {
            $profileConfig->setActive(false);
//            $profileConfig->setMessage('The profile is disabled. Please contact your account manager.');
        } else {
            $profileConfig->setActive(true);
            $profileConfig->setCountryCodesBilling(explode(',', $responseResult['merchantConfig']['country-code-billing']));
            $profileConfig->setCountryCodesDelivery(explode(',', $responseResult['merchantConfig']['country-code-delivery']));
            $profileConfig->setCurrencies(explode(',', $responseResult['merchantConfig']['currency']));

            $installmentConfigs = [];
            foreach (PaymentMethods::getNames() as $paymentMethodName) {
                $paymentMethod = $this->modelManager->getRepository(Payment::class)->findOneBy(['name' => $paymentMethodName]);
                if ($paymentMethod === null) {
                    continue;
                }

                // gets the ratepay internal payment method code
                $ratepayMethodCode = strtolower(PaymentMethods::getRatepayPaymentMethod($paymentMethodName));
                $merchantConfig = $responseResult['merchantConfig'];

                if (((int)$merchantConfig['activation-status-' . $ratepayMethodCode]) === 1) {
                    // method is disabled.
                    continue;
                }

                if ($responseResult['installmentConfig']['interestrate-min'] > 0 &&
                    PaymentMethods::isZeroPercentInstallment($paymentMethod)
                ) {
                    // if `interestrate-min` greater than zero, the profile has an installment config, but NOT a zeropercent-config
                    continue;
                }

                if (((int)$responseResult['installmentConfig']['interestrate-min']) === 0 &&
                    PaymentMethods::isNormalInstallment($paymentMethod)
                ) {
                    // if `interestrate-min` is zero, the profile has is a zero-percent installment config
                    continue;
                }

                $paymentMethodConfig = new ConfigPayment();
                $paymentMethodConfig->setProfileConfig($profileConfig);
                $paymentMethodConfig->setPaymentMethod($paymentMethod);
                $paymentMethodConfig->setAllowB2B($merchantConfig['b2b-' . $ratepayMethodCode] === 'yes');
                $paymentMethodConfig->setLimitMin($merchantConfig['tx-limit-' . $ratepayMethodCode . '-min']);
                $paymentMethodConfig->setLimitMax($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max']);
                $paymentMethodConfig->setLimitMaxB2b($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max-b2b']);
                $paymentMethodConfig->setAllowDifferentAddresses($merchantConfig['delivery-address-' . $ratepayMethodCode] === 'yes');
                $this->modelManager->persist($paymentMethodConfig);

                if (PaymentMethods::isInstallment($paymentMethod)) {
                    $paymentFirstDay = explode(',', $responseResult['installmentConfig']['valid-payment-firstdays']);
                    $installmentConfig = new ConfigInstallment();
                    $installmentConfig->setPaymentConfig($paymentMethodConfig);
                    $installmentConfig->setMonthsAllowed(explode(',', $responseResult['installmentConfig']['month-allowed']));
                    $installmentConfig->setDebitAllowed(in_array(2, $paymentFirstDay, false));
                    $installmentConfig->setBankTransferAllowed(in_array(28, $paymentFirstDay, false));
                    $installmentConfig->setRateMinNormal($responseResult['installmentConfig']['rate-min-normal']);
                    $installmentConfigs[] = $installmentConfig;
                }
            }

            try {
                $this->modelManager->flush();
                foreach ($installmentConfigs as $installmentConfig) {
                    $this->modelManager->persist($installmentConfig);
                }
                $this->modelManager->flush();
                $this->logger
                    ->info('Ratepay: Profile_Request for profileId ' . $profileConfig->getProfileId() . ' has been successfully saved');
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
        return true;
    }

    public function deletePaymentConfigs(array $profileConfigEntityIds = [])
    {
        $qb = $this->modelManager->createQueryBuilder();
        $qb->delete(ConfigPayment::class, 'e');
        if (count($profileConfigEntityIds)) {
            $qb->where($qb->expr()->in('e.profileConfig', $profileConfigEntityIds));
        }
        $qb->getQuery()->execute();
    }
}
