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
     * if the first parameter is a array than the function will not fetch the installment data from the gateway.
     * the array must contains the installment data from the gateway. you can get it with the function `getInstallmentPlan`
     * @param array|string $countryCode
     * @param int $shopId
     * @param string $paymentMethodName
     * @param boolean $isBackend
     * @param float $totalAmount
     * @param string $type //TODO better name
     * @param int $paymentFirstDate
     * @param float $value //TODO better name
     * @return mixed
     */
    public function initInstallmentData($countryCode, $shopId = null, $paymentMethodName = null, $isBackend = null, $totalAmount = null, $type = null, $paymentFirstDate = null, $value = null) {
        $plan = is_array($countryCode) ? $countryCode : $this->getInstallmentPlan($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount, $type, $value);

        $this->sessionHelper->setInstallmentDetails(
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
            $paymentFirstDate
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
        $installmentBuilder->setSecurityCode($profileConfig->getSecurityCode());
        return $installmentBuilder;
    }

}
