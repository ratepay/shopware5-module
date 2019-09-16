<?php


namespace RpayRatePay\Services\Methods;


use RatePAY\Frontend\InstallmentBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;

class InstallmentService
{


    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;

    /**
     * @var ConfigService
     */
    protected $configService;

    public function __construct(
        ProfileConfigService $profileConfigService,
        ConfigService $configService
    )
    {
        $this->profileConfigService = $profileConfigService;
        $this->configService = $configService;
    }

    /**
     * @param $countryCode
     * @param $shopId
     * @param $paymentMethodName
     * @param bool $isBackend
     * @return InstallmentBuilder
     */
    public function getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend = false)
    {
        //TODO: put magic strings in consts
        $zeroPercentInstallment = $paymentMethodName === PaymentMethods::PAYMENT_INSTALLMENT0;

        $profileConfig = $this->profileConfigService->getProfileConfig($countryCode, $shopId, $isBackend, $zeroPercentInstallment);

        //TODO factory ?
        $installmentBuilder = new InstallmentBuilder($profileConfig->isSandbox());
        $installmentBuilder->setProfileId($profileConfig->getProfileId());
        $installmentBuilder->setSecurityCode($this->configService->getSecurityCodeKey($profileConfig->getCountry(), $profileConfig->isBackend()));
        return $installmentBuilder;
    }
}
