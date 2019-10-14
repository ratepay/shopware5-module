<?php


namespace RpayRatePay\Services;


use Exception;
use RatePAY\Frontend\InstallmentBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Address;
use Shopware\Models\Shop\Shop;

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
    /**
     * @var ModelManager
     */
    private $modelManager;
    private $pluginDir;

    public function __construct(
        ModelManager $modelManager,
        ProfileConfigService $profileConfigService,
        ConfigService $configService,
        SessionHelper $sessionHelper,
        $pluginDir
    )
    {
        $this->modelManager = $modelManager;
        $this->profileConfigService = $profileConfigService;
        $this->configService = $configService;
        $this->sessionHelper = $sessionHelper;
        $this->pluginDir = $pluginDir;
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

    public function getInstallmentPlanTemplate($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount, $type, $value) {
        $installmentBuilder = $this->getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend);
        return $installmentBuilder->getInstallmentPlanByTemplate(
            file_get_contents($this->getTemplate('template.installmentPlan.html', $isBackend)),
            $totalAmount,
            $type,
            $value
        );
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

        $shop = $this->modelManager->find(Shop::class, $shopId); //TODO improve performance

        $installmentBuilder = new InstallmentBuilder(
            $profileConfig->isSandbox(),
            $profileConfig->getProfileId(),
            $profileConfig->getSecurityCode(),
            strtoupper(substr($shop->getLocale()->getLocale(), 0, 2)),
            $countryCode
        );
        return $installmentBuilder;
    }

    public function getInstallmentCalculatorTemplate(Address $billingAddress, $shopId, $paymentMethodName, $isBackend, $totalAmount)
    {
        $bankData = $this->sessionHelper->getBankData($billingAddress);

        $installmentBuilder = $this->getInstallmentBuilder($billingAddress->getCountry()->getIso(), $shopId, $paymentMethodName, $isBackend);
        $template = file_get_contents($this->getTemplate('template.installmentCalculator.html', $isBackend));
        $htmlCalculator = $installmentBuilder->getInstallmentCalculatorByTemplate($totalAmount, $template);
        $htmlCalculator = \RatePAY\Service\Util::templateReplace(
            $htmlCalculator,
            [
                'customer_name' => $billingAddress->getFirstname() . ' '.$billingAddress->getLastname(),
                'bank_data_iban' => $bankData ? ($bankData->getIban() ? $bankData->getIban() : $bankData->getAccountNumber()) : null,
                'bank_data_bankcode' => $bankData ? $bankData->getBankCode() : null,
            ]
        );
        return $htmlCalculator;
    }

    protected function getTemplate($templateName, $isBackend)
    {
        if($isBackend) {
            throw new \Exception('not implemented');
        }

        //TODO add filter or something like that.
        return $this->pluginDir.
            DIRECTORY_SEPARATOR.
            'Resources'.
            DIRECTORY_SEPARATOR.
            'templates'.
            DIRECTORY_SEPARATOR.
            ($isBackend ? 'backend':'frontend').
            DIRECTORY_SEPARATOR.
            $templateName;
    }

}
