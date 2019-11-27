<?php

namespace RpayRatePay\Services\Config;

use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Request\ProfileRequestService;
use Shopware\Components\Model\ModelManager;

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

    /**
     * Sends a Profile_request and saves the data into the Database
     *
     * @param ProfileConfig $profileConfig
     * @param $isZeroInstallment
     * @return bool
     */
    public function writeRatepayConfig(ProfileConfig $profileConfig)
    {
        try {
            $this->profileRequestService->setProfileConfig($profileConfig);
            $response = $this->profileRequestService->doRequest();
            $profileConfig = $this->profileRequestService->getProfileConfig();

            // if response is failed, we will try as a production request
            if ($response->getReasonCode() == 120 && $profileConfig->isSandbox()) {
                $profileConfig->setSandbox(false);
                $response = $this->profileRequestService->doRequest();
            } else if ($response->isSuccessful() === false) {
                $this->logger->error(
                    'RatePAY: Profile_Request failed for profileId ' . $profileConfig->getProfileId()
                );
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'RatePAY: Profile_Request failed for profileId ' . $profileConfig->getProfileId()
            );
            return false;
        }
        $responseResult = $response->getResult();
        if (!is_array($responseResult) || $response === false) {
            $this->logger
                ->notice('RatePAY: Profile_Request for profileId ' . $profileConfig->getProfileId() . ' was empty ');
            return false;
        }

        $countries = explode(',', $responseResult['merchantConfig']['country-code-billing']);
        foreach ($countries as $country) {

            $entity = $this->modelManager->find(ProfileConfig::class, [
                'countryCodeBilling' => $country,
                'backend' => $profileConfig->isBackend(),
                'shopId' => $profileConfig->getShopId(),
                'isZeroPercentInstallment' => $profileConfig->isZeroPercentInstallment()
            ]);

            if ($entity) {
                return true; //profile id has been already loaded
            }

            /** @var ConfigPayment[] $paymentMethodsConfigs */
            $paymentMethodsConfigs = [];

            $entitiesToFlush = [];
            //INSERT INTO rpay_ratepay_config_payment AND sets $type[]
            foreach (array_keys(PaymentMethods::PAYMENTS) as $methodCode) {
                $ratepayMethodCode = strtolower(PaymentMethods::getRatepayPaymentMethod($methodCode));
                if ($profileConfig->isZeroPercentInstallment()) {
                    if ($ratepayMethodCode !== 'installment') {
                        continue;
                    }
                }

                //just save the method it is enabled
                if ($responseResult['merchantConfig']['activation-status-' . $ratepayMethodCode] == 2) {
                    $merchantConfig = $responseResult['merchantConfig'];
                    $configPaymentModel = new ConfigPayment();
                    $configPaymentModel->setB2b($merchantConfig['b2b-' . $ratepayMethodCode] == 'yes');
                    $configPaymentModel->setLimitMin($merchantConfig['tx-limit-' . $ratepayMethodCode . '-min']);
                    $configPaymentModel->setLimitMax($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max']);
                    $configPaymentModel->setLimitMaxB2b($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max-b2b']);
                    $configPaymentModel->setAllowDifferentAddresses($merchantConfig['delivery-address-' . $ratepayMethodCode] == 'yes');

                    $this->modelManager->persist($configPaymentModel);
                    $entitiesToFlush[] = $configPaymentModel;
                    $paymentMethodsConfigs[$methodCode] = $configPaymentModel;
                }
            }

            if (count($paymentMethodsConfigs) > 0) {
                $this->modelManager->flush(array_values($paymentMethodsConfigs));
            }

            //performs insert into the 'config installment' table
            if (isset($paymentMethodsConfigs[PaymentMethods::PAYMENT_INSTALLMENT0]) ||
                isset($paymentMethodsConfigs[PaymentMethods::PAYMENT_RATE])) {
                $configInstallmentModel = new ConfigInstallment();
                if (isset($paymentMethodsConfigs[PaymentMethods::PAYMENT_INSTALLMENT0])) {
                    $configInstallmentModel->setPaymentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_INSTALLMENT0]);
                }
                if (isset($paymentMethodsConfigs[PaymentMethods::PAYMENT_RATE])) {
                    $configInstallmentModel->setPaymentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_RATE]);
                }
                $configInstallmentModel->setMonthAllowed($responseResult['installmentConfig']['month-allowed']);
                $configInstallmentModel->setPaymentFirstDay($responseResult['installmentConfig']['valid-payment-firstdays']);
                $configInstallmentModel->setRateMinNormal($responseResult['installmentConfig']['rate-min-normal']);
                $configInstallmentModel->setInterestRateDateDefault($responseResult['installmentConfig']['interestrate-default']);

                $this->modelManager->persist($configInstallmentModel);
                $entitiesToFlush[] = $configInstallmentModel;
            }
            $this->modelManager->flush($entitiesToFlush);
            $entitiesToFlush = [];

            $configModel = new ProfileConfig();
            $configModel->setProfileId($responseResult['merchantConfig']['profile-id']);
            $configModel->setSecurityCode($profileConfig->getSecurityCode());
            if ($profileConfig->isZeroPercentInstallment()) {
                $configModel->setInstallment0Config($paymentMethodsConfigs[PaymentMethods::PAYMENT_INSTALLMENT0]);
            } else {
                $configModel->setInvoiceConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_INVOICE]);
                $configModel->setInstallmentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_RATE]);
                $configModel->setDebitConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_DEBIT]);
                $configModel->setInstallmentDebitConfig(null); // TODO why is there no value?
                $configModel->setPrepaymentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_PREPAYMENT]);
            }
            $configModel->setCountryCodeBilling(strtoupper($country));
            $configModel->setCountryCodeDelivery(strtoupper($responseResult['merchantConfig']['country-code-delivery']));
            $configModel->setCurrency(strtoupper($responseResult['merchantConfig']['currency']));
            $configModel->setZeroPercentInstallment($profileConfig->isZeroPercentInstallment());
            $configModel->setSandbox($profileConfig->isSandbox());
            $configModel->setBackend($profileConfig->isBackend());
            $configModel->setShopId($profileConfig->getShopId());

            $this->modelManager->persist($configModel);
            $entitiesToFlush[] = $configModel;

            try {
                $this->modelManager->flush($entitiesToFlush);
                $this->logger
                    ->info('RatePAY: Profile_Request for profileId ' . $profileConfig->getProfileId() . ' has been successfully saved');
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return false;
            }
        }
        return true;
    }
}
