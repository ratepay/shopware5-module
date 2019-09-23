<?php

namespace RpayRatePay\Services\Config;

use Doctrine\ORM\OptimisticLockException;
use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\PaymentMethodsService;
use Shopware\Components\Model\ModelManager;

class WriterService
{
    private $db;

    /** @var ModelManager */
    protected $modelManager;
    /**
     * @var PaymentMethodsService
     */
    protected $paymentMethodsService;
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ModelManager $modelManager,
        PaymentMethodsService $paymentMethodsService,
        Logger $logger
    )
    {
        $this->db = $modelManager->getConnection();
        $this->modelManager = $modelManager;
        $this->paymentMethodsService = $paymentMethodsService;
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
     * @param string $profileId
     * @param string $securityCode
     * @param int $shopId
     * @param string $country
     * @param bool $backend
     *
     * @return bool
     */
    public function writeRatepayConfig($profileId, $securityCode, $shopId, $country, $backend = false)
    {
        $factory = new ModelFactory(null, $backend); //TODO service
        $data = [
            'profileId' => $profileId,
            'securityCode' => $securityCode
        ];

        try {
            $response = $factory->callProfileRequest($data);
        } catch (Exception $e) {
            $this->logger->error(
                'RatePAY: Profile_Request failed for profileId ' . $profileId
            );
            return false;
        }

        if (!is_array($response) || $response === false) {
            $this->logger
                ->info('RatePAY: Profile_Request for profileId ' . $profileId . ' was empty ');
            return false;
        }

        $payments = ['invoice', 'elv', 'installment', 'prepayment'];
        $entitiesToFlush = [];
        /** @var ConfigPayment[] $type */
        $type = [];
        //INSERT INTO rpay_ratepay_config_payment AND sets $type[]
        foreach ($payments as $payment) {
            if (strstr($profileId, '_0RT') !== false) {
                if ($payment !== 'installment') {
                    continue;
                }
            }
            $configPaymentModel = new ConfigPayment();
            $configPaymentModel->setStatus($response['result']['merchantConfig']['activation-status-' . $payment]);
            $configPaymentModel->setB2b($response['result']['merchantConfig']['b2b-' . $payment] == 'yes');
            $configPaymentModel->setLimitMin($response['result']['merchantConfig']['tx-limit-' . $payment . '-min']);
            $configPaymentModel->setLimitMax($response['result']['merchantConfig']['tx-limit-' . $payment . '-max']);
            $configPaymentModel->setLimitMaxB2b($response['result']['merchantConfig']['tx-limit-' . $payment . '-max-b2b']);
            $configPaymentModel->setAddress($response['result']['merchantConfig']['delivery-address-' . $payment] == 'yes' ? 1 : 0);

            $this->modelManager->persist($configPaymentModel);
            $entitiesToFlush[] = $configPaymentModel;
            $type[$payment] = $configPaymentModel;
        }

        $this->modelManager->flush(array_values($type));

        //performs insert into the 'config installment' table
        if ($response['result']['merchantConfig']['activation-status-installment'] == 2) {
            $configInstallmentModel = new ConfigInstallment();
            $configInstallmentModel->setPaymentConfig($type['installment']);
            $configInstallmentModel->setMonthAllowed($response['result']['installmentConfig']['month-allowed']);
            $configInstallmentModel->setPaymentFirstDay($response['result']['installmentConfig']['valid-payment-firstdays']);
            $configInstallmentModel->setRateMinNormal($response['result']['installmentConfig']['rate-min-normal']);
            $configInstallmentModel->setInterestRateDateDefault($response['result']['installmentConfig']['interestrate-default']);

            $this->modelManager->persist($configInstallmentModel);
            $entitiesToFlush[] = $configInstallmentModel;
        }
        $this->modelManager->flush($entitiesToFlush);
        $entitiesToFlush = [];

        //updates 0% field in rpay_ratepay_config or inserts into rpay_ratepay_config THIS MEANS WE HAVE TO SEND the 0RT profiles last
        if (strstr($profileId, '_0RT') !== false) {
            /** @var ProfileConfig $configModel */
            $configModel = $this->modelManager->getRepository(ProfileConfig::class)->findOneBy(['profileId' => substr($profileId, 0, -4)]);
            $configModel->setInstallment0Config($type['installment']);
            try {
                $this->modelManager->flush($configModel);
            } catch (OptimisticLockException $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
        } else {
            $configModel = new ProfileConfig();
            $configModel->setProfileId($response['result']['merchantConfig']['profile-id']);
            $configModel->setSecurityCode($securityCode);
            $configModel->setInvoiceConfig($type['invoice']);
            $configModel->setInstallmentConfig($type['installment']);
            $configModel->setDebitConfig($type['elv']);
            $configModel->setInstallment0Config(null); // TODO why there is no value?
            $configModel->setInstallmentDebitConfig(null); // TODO why there is no value?
            $configModel->setPrepaymentConfig($type['prepayment']);
            $configModel->setCountryCodeBilling(strtoupper($response['result']['merchantConfig']['country-code-billing']));
            $configModel->setCountryCodeDelivery(strtoupper($response['result']['merchantConfig']['country-code-delivery']));
            $configModel->setCurrency(strtoupper($response['result']['merchantConfig']['currency']));
            $configModel->setCountry(strtoupper($country));
            $configModel->setSandbox($response['sandbox'] == 1);
            $configModel->setBackend($backend == 1);
            $configModel->setShopId($shopId);

            $this->modelManager->persist($configModel);
            $entitiesToFlush[] = $configModel;

            try {
                $this->modelManager->flush($entitiesToFlush);
                $this->paymentMethodsService->enableMethods();

                return true;
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return false;
            }
        }
    }
}
