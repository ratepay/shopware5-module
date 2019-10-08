<?php


namespace RpayRatePay\Services;


use Exception;
use RatePAY\Frontend\InstallmentBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
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
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        ProfileConfigService $profileConfigService,
        ConfigService $configService,
        SessionHelper $sessionHelper
    )
    {
        $this->profileConfigService = $profileConfigService;
        $this->configService = $configService;
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * @param string $countryCode
     * @param int $shopId
     * @param string $paymentMethodName
     * @param boolean $isBackend
     * @param float $totalAmount
     * @param string $type //TODO better name
     * @param string $paymentSubtype
     * @param float $value //TODO better name
     * @return mixed
     */
    public function initInstallmentData($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount, $type, $paymentSubtype, $value) {
        $plan = $this->getInstallmentPlan($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount, $type, $value);

        $this->sessionHelper->setInstallmentData(
            $plan['totalAmount'],
            $plan['amount'],
            $plan['interestRate'],
            $plan['interestAmount'],
            $plan['serviceCharge'],
            $plan['annualPercentageRate'],
            $plan['monthlyDebitInterest'],
            $plan['numberOfRatesFull'],
            $plan['rate'],
            $plan['lastRate'],
            $paymentSubtype //$plan['paymentFirstday'] //todo this is the paymentFirstDay
        );
        return $plan;
    }

    public function getInstallmentPlan($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount, $type, $value) {
        $installmentBuilder = $this->getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend);
        $result = $installmentBuilder->getInstallmentPlanAsJson($totalAmount, $type, $value);
        return json_decode($result, true);
    }

    public function getInstallmentCalculator($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount) {
        $installmentBuilder = $this->getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend);
        $result = $installmentBuilder->getInstallmentCalculatorAsJson($totalAmount);
        return json_decode($result, true);
    }

    /**
     * @param $countryCode
     * @param $shopId
     * @param $paymentMethodName
     * @param bool $isBackend
     * @return InstallmentBuilder
     */
    protected function getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend = false)
    {
        $zeroPercentInstallment = $paymentMethodName === PaymentMethods::PAYMENT_INSTALLMENT0;

        $profileConfig = $this->profileConfigService->getProfileConfig($countryCode, $shopId, $isBackend, $zeroPercentInstallment);

        $installmentBuilder = new InstallmentBuilder($profileConfig->isSandbox());
        $installmentBuilder->setProfileId($profileConfig->getProfileId());
        $installmentBuilder->setSecurityCode($this->configService->getSecurityCode($profileConfig->getCountry(), $profileConfig->getShopId(), $profileConfig->isBackend()));
        return $installmentBuilder;
    }

}
