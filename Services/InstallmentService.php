<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


use Enlight_Template_Default;
use Enlight_Template_Manager;
use RatePAY\Frontend\InstallmentBuilder;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\DTO\PaymentConfigSearch;
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
     * @param PaymentConfigSearch $configSearch
     * @param InstallmentRequest $requestDto
     * @return mixed
     */
    public function initInstallmentData(PaymentConfigSearch $configSearch, InstallmentRequest $requestDto)
    {

        $plan = $this->getInstallmentPlan($configSearch, $requestDto);

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
            $requestDto->getPaymentType(),
            $requestDto
        );

        return $plan;
    }

    public function getInstallmentPlan(PaymentConfigSearch $configSearch, InstallmentRequest $requestDto)
    {
        $installmentBuilder = $this->getInstallmentBuilder($configSearch);
        $result = $installmentBuilder->getInstallmentPlanAsJson($requestDto->getTotalAmount(), $requestDto->getType(), $requestDto->getValue());
        return json_decode($result, true);
    }

    /**
     * @param PaymentConfigSearch $configSearch
     * @return InstallmentBuilder
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function getInstallmentBuilder(PaymentConfigSearch $configSearch)
    {
        $shop = $this->modelManager->find(Shop::class, $configSearch->getShop());

        $paymentMethodConfig = $this->profileConfigService->getPaymentConfiguration($configSearch);

        $profileConfig = $paymentMethodConfig->getProfileConfig();

        return new InstallmentBuilder(
            $profileConfig->isSandbox(),
            $profileConfig->getProfileId(),
            $profileConfig->getSecurityCode(),
            strtoupper(substr($shop->getLocale()->getLocale(), 0, 2)),
            $configSearch->getBillingCountry()
        );
    }

    public function getInstallmentPlanTemplate(PaymentConfigSearch $configSearch, InstallmentRequest $requestDto)
    {
        $planData = $this->getInstallmentPlan($configSearch, $requestDto);

        /** @var Enlight_Template_Default $template */
        $template = $this->templateManager->createTemplate('frontend/plugins/payment/ratepay/installment/plan.tpl');

        $template->assign('ratepay', [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'plan' => $planData
        ]);
        return $template->fetch();
    }

    public function getInstallmentCalculator(PaymentConfigSearch $configSearch, $totalAmount)
    {
        $installmentBuilder = $this->getInstallmentBuilder($configSearch);
        $result = $installmentBuilder->getInstallmentCalculatorAsJson($totalAmount);
        return json_decode($result, true);
    }

    public function getInstallmentCalculatorVars(PaymentConfigSearch $configSearch, $totalAmount)
    {
        $calculatorData = $this->getInstallmentCalculator($configSearch, $totalAmount);

        return array_merge([], [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'calculator' => $calculatorData
        ]);
    }

}
