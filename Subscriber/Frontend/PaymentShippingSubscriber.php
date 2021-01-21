<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;


use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\InstallmentService;
use RpayRatePay\Util\BankDataUtil;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware_Components_Config;
use Shopware_Controllers_Frontend_Checkout;

class PaymentShippingSubscriber implements SubscriberInterface
{


    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;
    /**
     * @var DfpService
     */
    protected $dfpService;
    /**
     * @var ConfigService
     */
    protected $configService;
    protected $pluginDir;
    /**
     * @var ShopContextInterface|null
     */
    protected $context;
    /**
     * @var SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var InstallmentService
     */
    protected $installmentService;
    /**
     * @var Shopware_Components_Config
     */
    private $config;

    public function __construct(
        Shopware_Components_Config $config,
        SessionHelper $sessionHelper,
        ContextService $contextService,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        InstallmentService $installmentService,
        DfpService $dfpService,
        $pluginDir
    )
    {
        $this->context = $contextService->getContext();
        $this->configService = $configService;
        $this->dfpService = $dfpService;
        $this->sessionHelper = $sessionHelper;
        $this->profileConfigService = $profileConfigService;
        $this->installmentService = $installmentService;
        $this->pluginDir = $pluginDir;
        $this->config = $config;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'onShippingPaymentAction'
        ];
    }

    public function onShippingPaymentAction(Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $view = $subject->View();

        if ($view->getAssign('sPayments') === null) {
            return;
        }

        $billingAddress = $this->sessionHelper->getBillingAddress();
        $shippingAddress = $this->sessionHelper->getShippingAddress();
        $currency = $this->config->get('currency');

        $sUserData = $view->getAssign('sUserData');
        if (isset($sUserData['additional']['payment']['name'])) {
            // this is a little bit tricky. Shopware set another ID to the data object. To use this value is a
            // little bit safer cause related to this value the payment section will show
            $paymentMethodName = $sUserData['additional']['payment']['name'];
        } else {
            $paymentMethodName = $this->sessionHelper->getPaymentMethod();
        }

        if (PaymentMethods::exists($paymentMethodName)) {
            $paymentMethodConfig = $this->profileConfigService->getPaymentConfiguration((new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethodName)
                ->setBackend(false)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop($this->context->getShop())
                ->setCurrency($currency)
            );

            if ($paymentMethodConfig === null) {
                return;
            }

            $data = [
                'sandbox' => $paymentMethodConfig->getProfileConfig()->isSandbox(),
                'agb' => 'https://www.ratepay.com/legal'
            ];

            $view->assign('ratepay', array_merge($view->getAssign('ratepay') ? : [], $data));
        }

        // fix static form data
        $viewParams = $view->getAssign();
        if (isset($viewParams['sFormData']['ratepay'])) {
            $bankData = $this->sessionHelper->getBankData($billingAddress);
            $accountHolders = BankDataUtil::getAvailableAccountHolder($billingAddress, $bankData);
            $viewParams['sFormData']['ratepay']['bank_account']['accountHolder'] = [
                'list' => $accountHolders,
                'selected' => $bankData && $bankData->getAccountHolder() ? $bankData->getAccountHolder() : $accountHolders[0]
            ];
        }
        $view->assign($viewParams);
    }


}
