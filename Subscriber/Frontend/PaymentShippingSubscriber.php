<?php


namespace RpayRatePay\Subscriber\Frontend;


use Enlight\Event\SubscriberInterface;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Frontend_Checkout;

class PaymentShippingSubscriber implements SubscriberInterface
{


    /**
     * @var ProfileConfigService
     */
    private $profileConfigService;
    /**
     * @var DfpService
     */
    private $dfpService;
    /**
     * @var ConfigService
     */
    private $configService;
    private $pluginDir;
    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface|null
     */
    private $context;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        ModelManager $modelManager,
        SessionHelper $sessionHelper,
        ContextService $contextService,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        DfpService $dfpService,
        $pluginDir
    )
    {
        $this->modelManager = $modelManager;
        $this->context = $contextService->getContext();
        $this->configService = $configService;
        $this->dfpService = $dfpService;
        $this->sessionHelper = $sessionHelper;
        $this->profileConfigService = $profileConfigService;
        $this->pluginDir = $pluginDir;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'onShippingPaymentAction'
        ];
    }

    public function onShippingPaymentAction(\Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $view = $subject->View();

        if($view->getAssign('sPayments') === null) {
            return;
        }

        $sUserData = $view->getAssign('sUserData');
        if(isset($sUserData['additional']['payment']['name'])) {
            // this is a little bit tricky. Shopware set another ID to the data object. To use this value is a
            // little bit safer cause related to this value the payment section will show
            $paymentMethodName = $sUserData['additional']['payment']['name'];
        } else {
            $paymentMethodName = $this->sessionHelper->getPaymentMethod();
        }

        if (PaymentMethods::exists($paymentMethodName)) {
            $billingAddress = $this->sessionHelper->getBillingAddress();
            $profileConfig = $this->profileConfigService->getProfileConfig(
                $billingAddress->getCountry()->getIso(),
                $this->context->getShop()->getId(),
                false,
                PaymentMethods::isZeroPercentInstallment($paymentMethodName)
            );

            $data = [
                'sandbox' => $profileConfig->isSandbox(),
            ];

            if($view->getAssign('ratepay')) {
                $data = array_merge($view->getAssign('ratepay'), $data);
            }
            $view->assign('ratepay', $data);

            if(PaymentMethods::isInstallment($paymentMethodName)) {

                $template = file_get_contents($this->pluginDir.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'template.installmentCalculator.html');
                $ib = new \RatePAY\Frontend\InstallmentBuilder($profileConfig->isSandbox()); // true = sandbox mode
                $ib->setProfileId($profileConfig->getProfileId());
                $ib->setSecuritycode($profileConfig->getSecurityCode());
                $htmlCalculator = $ib->getInstallmentCalculatorByTemplate(600, $template);

                $view->assign('installmentCalculator',
                    [
                        'html' => $htmlCalculator,
                        'totalAmount' => floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']), //TODO
                    ]
                );
            }
        }
    }



}
