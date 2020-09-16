<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


use Enlight_Template_Default;
use Enlight_Template_Manager;
use RatePAY\Frontend\InstallmentBuilder;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\LanguageHelper;
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
    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;

    public function __construct(
        ModelManager $modelManager,
        ProfileConfigService $profileConfigService,
        ConfigService $configService,
        SessionHelper $sessionHelper,
        Enlight_Template_Manager $templateManager,
        $pluginDir
    )
    {
        $this->modelManager = $modelManager;
        $this->profileConfigService = $profileConfigService;
        $this->configService = $configService;
        $this->sessionHelper = $sessionHelper;
        $this->pluginDir = $pluginDir;
        $this->templateManager = $templateManager;
    }

    /**
     * if the first parameter is a array than the function will not fetch the installment data from the gateway.
     * the array must contains the installment data from the gateway. you can get it with the function `getInstallmentPlan`
     * @param Address $billingAddress
     * @param int $shopId
     * @param string $paymentMethodName
     * @param boolean $isBackend
     * @param InstallmentRequest $requestDto
     * @return mixed
     */
    public function initInstallmentData(
        Address $billingAddress,
        $shopId,
        $paymentMethodName,
        $isBackend,
        InstallmentRequest $requestDto
    )
    {

        $plan = $this->getInstallmentPlan($billingAddress, $shopId, $paymentMethodName, $isBackend, $requestDto);

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

    public function getInstallmentPlan(Address $billingAddress, $shopId, $paymentMethodName, $isBackend, InstallmentRequest $requestDto)
    {
        $installmentBuilder = $this->getInstallmentBuilder($billingAddress, $shopId, $paymentMethodName, $isBackend);
        $result = $installmentBuilder->getInstallmentPlanAsJson($requestDto->getTotalAmount(), $requestDto->getType(), $requestDto->getValue());
        return json_decode($result, true);
    }

    /**
     * @param Address $billingAddress
     * @param $shopId
     * @param $paymentMethodName
     * @param bool $isBackend
     * @return InstallmentBuilder
     */
    protected function getInstallmentBuilder(Address $billingAddress, $shopId, $paymentMethodName, $isBackend = false)
    {
        $paymentMethodName = $paymentMethodName instanceof Payment ? $paymentMethodName->getName() : $paymentMethodName;
        $zeroPercentInstallment = $paymentMethodName === PaymentMethods::PAYMENT_INSTALLMENT0;

        $profileConfig = $this->profileConfigService->getProfileConfig(
            $billingAddress->getCountry()->getIso(),
            $shopId,
            $isBackend,
            $zeroPercentInstallment
        );

        $shop = $this->modelManager->find(Shop::class, $shopId); //TODO improve performance

        $installmentBuilder = new InstallmentBuilder(
            $profileConfig->isSandbox(),
            $profileConfig->getProfileId(),
            $profileConfig->getSecurityCode(),
            strtoupper(substr($shop->getLocale()->getLocale(), 0, 2)),
            $billingAddress->getCountry()->getIso()
        );
        return $installmentBuilder;
    }

    public function getInstallmentPlanTemplate(Address $billingAddress, $shopId, $paymentMethodName, $isBackend, InstallmentRequest $requestDto)
    {
        $planData = $this->getInstallmentPlan($billingAddress, $shopId, $paymentMethodName, $isBackend, $requestDto);

        /** @var Enlight_Template_Default $template */
        $template = $this->templateManager->createTemplate('frontend/plugins/payment/ratepay/installment/plan.tpl');

        $template->assign('ratepay', [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'plan' => $planData
        ]);
        return $template->fetch();
    }

    public function getInstallmentCalculator(Address $billingAddress, $shopId, $paymentMethodName, $isBackend, $totalAmount)
    {
        $installmentBuilder = $this->getInstallmentBuilder($billingAddress, $shopId, $paymentMethodName, $isBackend);
        $result = $installmentBuilder->getInstallmentCalculatorAsJson($totalAmount);
        return json_decode($result, true);
    }

    public function getInstallmentCalculatorTemplate(Address $billingAddress, $shopId, $paymentMethodName, $isBackend, $totalAmount)
    {
        $bankData = $this->sessionHelper->getBankData($billingAddress);
        $calculatorData = $this->getInstallmentCalculator($billingAddress, $shopId, $paymentMethodName, $isBackend, $totalAmount);

        /** @var Enlight_Template_Default $template */
        $template = $this->templateManager->createTemplate('frontend/plugins/payment/ratepay/installment/calculator.tpl');
        $template->assign('ratepay', [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'calculator' => $calculatorData,
            'data' => [
                'customer_name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                'bank_data_iban' => $bankData ? ($bankData->getIban() ? $bankData->getIban() : $bankData->getAccountNumber()) : null,
                'bank_data_bankcode' => $bankData ? $bankData->getBankCode() : null,
            ]
        ]);
        return $template->fetch();
    }

}
