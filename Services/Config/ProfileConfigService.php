<?php


namespace RpayRatePay\Services\Config;


use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigInstallmentRepository;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Models\ProfileConfigRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment as PaymentMethod;

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

    /**
     * @param $countryIso
     * @param $shopId
     * @param bool $backend
     * @param bool $zeroPercentInstallment
     * @return ProfileConfig|null
     */
    public function getProfileConfig($countryIso, $shopId, $backend = false, $zeroPercentInstallment = false)
    {
        $profileId = $this->configService->getProfileId($countryIso, $zeroPercentInstallment, $backend);

        /** @var ProfileConfigRepository $repo */
        $repo = $this->modelManager->getRepository(ProfileConfig::class);
        return $repo->findOneByShopAndProfileId($profileId, $shopId);
    }

    // TODO naming is similar to `getPaymentConfig`, but do other stuffs

    /**
     * @param PaymentMethod $paymentMethodName string
     * @param ProfileConfig|$shopId
     * @param $countryIso
     * @param bool $backend
     * @return object|ConfigInstallment|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getInstallmentConfig($paymentMethodName, $shopId, $countryIso = null, $backend = false)
    {
        $config = $this->getPaymentConfig($paymentMethodName, $shopId, $countryIso,  $backend);
        return $this->modelManager->find(ConfigInstallment::class, $config->getRpayId());
    }


    /**
     * @param PaymentMethod $paymentMethod
     * @param $shopId ProfileConfig|string
     * @param $countryIso
     * @param $backend
     * @return ConfigPayment
     */
    protected function getPaymentConfig($paymentMethod, $shopId, $countryIso = null, $backend = null)
    {
        $paymentMethod = $paymentMethod instanceof PaymentMethod ? $paymentMethod->getName() : $paymentMethod;
        $profileConfig = $shopId instanceof ProfileConfig ? $shopId : $this->getProfileConfig(
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
