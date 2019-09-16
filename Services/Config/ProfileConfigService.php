<?php


namespace RpayRatePay\Services\Config;


use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigInstallmentRepository;
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

    public function getProfileConfig($countryIso, $shopId, $backend = false, $zeroPercentInstallment = false) {

        $profileId = $this->configService->getProfileId($countryIso, $shopId, $zeroPercentInstallment, $backend);

        /** @var ProfileConfigRepository $repo */
        $repo = $this->modelManager->getRepository(ProfileConfig::class);
        return $repo->findOneByShopAndProfileId($profileId, $shopId);
    }

    public function getInstallmentPaymentConfig($paymentMethodName, $shopId, $countryIso, $backend = false) {
        $profileConfig = $this->getProfileConfig($countryIso, $shopId, $backend, false); //TODO last parameter

        /** @var ConfigInstallmentRepository $repo */
        $repo = $this->modelManager->getRepository(ConfigInstallment::class);
        return $repo->findOneByPaymentConfig($profileConfig->getPaymentConfig($paymentMethodName));
    }
}
