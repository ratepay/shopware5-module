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

        $this->count = 0;
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
            foreach (PaymentMethods::getNames() as $methodCode) {
                $saveAsMethodCode = $methodCode;
                if (PaymentMethods::isInstallment($methodCode)) {
                    // we save all installment types to the installment fields
                    // (also the zero percent installment, cause it is also an installment)
                    $saveAsMethodCode = PaymentMethods::PAYMENT_RATE;
                }

                if (isset($paymentMethodsConfigs[$saveAsMethodCode])) {
                    // has been already created
                    continue;
                }

                // we set always a null value, so we will not get a "undefined index"-warning
                // (while setting the paymentConfigs to the ratepay config table)
                $paymentMethodsConfigs[$methodCode] = null;

                // gets the ratepay internal payment method code
                $ratepayMethodCode = strtolower(PaymentMethods::getRatepayPaymentMethod($methodCode));

                if ($profileConfig->isZeroPercentInstallment() &&
                    PaymentMethods::isZeroPercentInstallment($methodCode) == false) {
                    // the current payment method is a zero percent installment, but we have not requested a zero percent method
                    continue;
                }

                //just save the method if it is enabled
                if ($responseResult['merchantConfig']['activation-status-' . $ratepayMethodCode] == 2) {
                    $this->count++;
                    $merchantConfig = $responseResult['merchantConfig'];
                    $configPaymentModel = new ConfigPayment();
                    $configPaymentModel->setB2b($merchantConfig['b2b-' . $ratepayMethodCode] == 'yes');
                    $configPaymentModel->setLimitMin($merchantConfig['tx-limit-' . $ratepayMethodCode . '-min']);
                    $configPaymentModel->setLimitMax($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max']);
                    $configPaymentModel->setLimitMaxB2b($merchantConfig['tx-limit-' . $ratepayMethodCode . '-max-b2b']);
                    $configPaymentModel->setAllowDifferentAddresses($merchantConfig['delivery-address-' . $ratepayMethodCode] == 'yes');

                    $paymentMethodsConfigs[$saveAsMethodCode] = $configPaymentModel;
                    $this->modelManager->persist($configPaymentModel);
                    $this->modelManager->flush();

                    if (PaymentMethods::isInstallment($methodCode)) {
                        $configInstallmentModel = new ConfigInstallment();
                        $configInstallmentModel->setPaymentConfig($configPaymentModel);
                        $configInstallmentModel->setMonthAllowed($responseResult['installmentConfig']['month-allowed']);
                        $configInstallmentModel->setPaymentFirstDay($responseResult['installmentConfig']['valid-payment-firstdays']);
                        $configInstallmentModel->setRateMinNormal($responseResult['installmentConfig']['rate-min-normal']);
                        $configInstallmentModel->setInterestRateDateDefault($responseResult['installmentConfig']['interestrate-default']);

                        $this->modelManager->persist($configInstallmentModel);
                        $this->modelManager->flush();
                    }

                }
            }


            $entitiesToFlush = [];

            $configModel = new ProfileConfig();
            $configModel->setProfileId($responseResult['merchantConfig']['profile-id']);
            $configModel->setSecurityCode($profileConfig->getSecurityCode());
            $configModel->setInvoiceConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_INVOICE]);
            $configModel->setInstallmentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_RATE]);
            $configModel->setDebitConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_DEBIT]);
            $configModel->setPrepaymentConfig($paymentMethodsConfigs[PaymentMethods::PAYMENT_PREPAYMENT]);
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
