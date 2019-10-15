<?php


namespace RpayRatePay\Services;


use Exception;
use RatePAY\Frontend\InstallmentBuilder;
use RatePAY\Service\Util;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Address;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

// TODO improve this service ....
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
     * @param InstallmentRequest $requestDto
     * @return mixed
     */
    public function initInstallmentData(
        $countryCode,
        $shopId,
        $paymentMethodName,
        $isBackend,
        InstallmentRequest $requestDto
    )
    {

        $plan = $this->getInstallmentPlan($countryCode, $shopId, $paymentMethodName, $isBackend, $requestDto);

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
            $requestDto->getPaymentFirstDay(),
            $requestDto
        );

        return $plan;
    }

    public function getInstallmentPlan($countryCode, $shopId, $paymentMethodName, $isBackend, InstallmentRequest $requestDto)
    {
        $installmentBuilder = $this->getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend);
        $result = $installmentBuilder->getInstallmentPlanAsJson($requestDto->getTotalAmount(), $requestDto->getType(), $requestDto->getValue());
        return json_decode($result, true);
    }

    public function getInstallmentPlanTemplate($countryCode, $shopId, $paymentMethodName, $isBackend, InstallmentRequest $requestDto)
    {
        $installmentBuilder = $this->getInstallmentBuilder($countryCode, $shopId, $paymentMethodName, $isBackend);
        return $installmentBuilder->getInstallmentPlanByTemplate(
            file_get_contents($this->getTemplate('template.installmentPlan.html', $isBackend)),
            $requestDto->getTotalAmount(),
            $requestDto->getType(),
            $requestDto->getValue(),
            $requestDto->getPaymentFirstDay()
        );
    }

    public function getInstallmentCalculator($countryCode, $shopId, $paymentMethodName, $isBackend, $totalAmount)
    {
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
        $paymentMethodName = $paymentMethodName instanceof Payment ? $paymentMethodName->getName() : $paymentMethodName;
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
        $htmlCalculator = Util::templateReplace(
            $htmlCalculator,
            [
                'customer_name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'bank_data_iban' => $bankData ? ($bankData->getIban() ? $bankData->getIban() : $bankData->getAccountNumber()) : null,
                'bank_data_bankcode' => $bankData ? $bankData->getBankCode() : null,
            ]
        );
        return $htmlCalculator;
    }

    protected function getTemplate($templateName, $isBackend)
    {
        if ($isBackend) {
            //TODO implement
            throw new Exception('not implemented');
        }

        //TODO add filter or something like that.
        return $this->pluginDir .
            DIRECTORY_SEPARATOR .
            'Resources' .
            DIRECTORY_SEPARATOR .
            'templates' .
            DIRECTORY_SEPARATOR .
            ($isBackend ? 'backend' : 'frontend') .
            DIRECTORY_SEPARATOR .
            $templateName;
    }

}
