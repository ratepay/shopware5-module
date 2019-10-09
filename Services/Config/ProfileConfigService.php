<?php


namespace RpayRatePay\Services\Config;


use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigInstallmentRepository;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Models\ProfileConfigRepository;
use Shopware\Components\Model\ModelManager;

class ProfileConfigService
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        ConfigService $configService,
        ModelManager $modelManager
    )
    {
        $this->configService = $configService;
        $this->modelManager = $modelManager;
    }

    public function getProfileConfig($countryIso, $shopId, $backend = false, $zeroPercentInstallment = false)
    {
        $profileId = $this->configService->getProfileId($countryIso, $zeroPercentInstallment, $backend);

        /** @var ProfileConfigRepository $repo */
        $repo = $this->modelManager->getRepository(ProfileConfig::class);
        return $repo->findOneByShopAndProfileId($profileId, $shopId);
    }

    // TODO naming is similar to `getPaymentConfig`, but do other stuffs
    public function getInstallmentConfig($paymentMethodName, $shopId, $countryIso, $backend = false)
    {
        $config = $this->getPaymentConfig($paymentMethodName, $shopId, $countryIso, $backend);
        return $this->modelManager->find(ConfigInstallment::class, $config->getRpayId());
    }


    /**
     * @param $shopId
     * @param $countryIso
     * @param $paymentMethod
     * @param $backend
     * @return ConfigPayment
     */
    protected function getPaymentConfig($shopId, $countryIso, $paymentMethod, $backend)
    {
        $profileConfig = $this->getProfileConfig(
            $countryIso,
            $shopId,
            $backend,
            $paymentMethod == PaymentMethods::PAYMENT_INSTALLMENT0
        );
        return $this->getPaymentConfigForProfileAndMethod($profileConfig, $paymentMethod);
    }

    public function getPaymentConfigForProfileAndMethod(ProfileConfig $profileConfig, $paymentMethod) {
        switch($paymentMethod) {
            case PaymentMethods::PAYMENT_DEBIT:
                return $profileConfig->getDebitConfig();
            case PaymentMethods::PAYMENT_INSTALLMENT0:
                return $profileConfig->getInstallment0Config();
            case PaymentMethods::PAYMENT_INVOICE:
                return $profileConfig->getInvoiceConfig();
            case PaymentMethods::PAYMENT_RATE:
                return $profileConfig->getInstallmentConfig();
            case PaymentMethods::PAYMENT_PREPAYMENT:
                return $profileConfig->getPrepaymentConfig();
        }
        throw new \Exception('unknown payment method '.$paymentMethod);
    }
}
