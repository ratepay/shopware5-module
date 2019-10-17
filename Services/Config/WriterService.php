<?php

namespace RpayRatePay\Services\Config;

use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\ModelFactory;
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
        $configInstallmentSql = 'TRUNCATE TABLE `' . $this->modelManager->getClassMetadata(ConfigInstallment::class)->getTableName() . '`;';
        $configSql = 'TRUNCATE TABLE `' . $this->modelManager->getClassMetadata(ProfileConfig::class)->getTableName() . '`;';
        $configPaymentSql = 'TRUNCATE TABLE `' . $this->modelManager->getClassMetadata(ConfigPayment::class)->getTableName() . '`;';
        try {
            $this->db->query("SET FOREIGN_KEY_CHECKS=0");
            $this->db->query($configSql);
            $this->db->query($configPaymentSql);
            $this->db->query($configInstallmentSql);
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
    public function writeRatepayConfig(ProfileConfig $profileConfig, $isZeroInstallment)
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

        $payments = ['invoice', 'elv', 'installment', 'prepayment'];
        $entitiesToFlush = [];
        /** @var ConfigPayment[] $type */
        $type = [];
        //INSERT INTO rpay_ratepay_config_payment AND sets $type[]
        foreach ($payments as $payment) {
            if ($isZeroInstallment) {
                if ($payment !== 'installment') {
                    continue;
                }
            }
            $configPaymentModel = new ConfigPayment();
            $configPaymentModel->setStatus($responseResult['merchantConfig']['activation-status-' . $payment]);
            $configPaymentModel->setB2b($responseResult['merchantConfig']['b2b-' . $payment] == 'yes');
            $configPaymentModel->setLimitMin($responseResult['merchantConfig']['tx-limit-' . $payment . '-min']);
            $configPaymentModel->setLimitMax($responseResult['merchantConfig']['tx-limit-' . $payment . '-max']);
            $configPaymentModel->setLimitMaxB2b($responseResult['merchantConfig']['tx-limit-' . $payment . '-max-b2b']);
            $configPaymentModel->setAddress($responseResult['merchantConfig']['delivery-address-' . $payment] == 'yes' ? 1 : 0);

            $this->modelManager->persist($configPaymentModel);
            $entitiesToFlush[] = $configPaymentModel;
            $type[$payment] = $configPaymentModel;
        }

        $this->modelManager->flush(array_values($type));

        //performs insert into the 'config installment' table
        if ($responseResult['merchantConfig']['activation-status-installment'] == 2) {
            $configInstallmentModel = new ConfigInstallment();
            $configInstallmentModel->setPaymentConfig($type['installment']);
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
        if ($isZeroInstallment) {
            $configModel->setInstallment0Config($type['installment']);
        } else {
            $configModel->setInvoiceConfig($type['invoice']);
            $configModel->setInstallmentConfig($type['installment']);
            $configModel->setDebitConfig($type['elv']);
            $configModel->setInstallmentDebitConfig(null); // TODO why there is no value?
            $configModel->setPrepaymentConfig($type['prepayment']);
        }
        $configModel->setCountryCodeBilling(strtoupper($responseResult['merchantConfig']['country-code-billing']));
        $configModel->setCountryCodeDelivery(strtoupper($responseResult['merchantConfig']['country-code-delivery']));
        $configModel->setCurrency(strtoupper($responseResult['merchantConfig']['currency']));
        $configModel->setCountry(strtoupper($profileConfig->getCountry()));
        $configModel->setSandbox($profileConfig->isSandbox());
        $configModel->setBackend($profileConfig->isBackend());
        $configModel->setShopId($profileConfig->getShopId());

        $this->modelManager->persist($configModel);
        $entitiesToFlush[] = $configModel;

        try {
            $this->modelManager->flush($entitiesToFlush);
            $this->logger
                ->info('RatePAY: Profile_Request for profileId ' . $profileConfig->getProfileId() . ' was successfully ');
            return true;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
    }
}
