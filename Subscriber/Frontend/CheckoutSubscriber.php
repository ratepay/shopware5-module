<?php

namespace RpayRatePay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Frontend_Checkout;

class CheckoutSubscriber implements SubscriberInterface
{

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var DfpService
     */
    protected $dfpService;
    /**
     * @var ShopContextInterface
     */
    protected $context;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var ProfileConfigService
     */
    private $profileConfigService;
    private $pluginDir;

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
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'extendTemplates',
        ];
    }

    public function extendTemplates(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if(!$request->isDispatched() ||
            $response->isException() ||
            !$view->hasTemplate() ||
            $request->getModuleName() != 'frontend' ||
            $request->getControllerName() != 'checkout'
        ) {
            return;
        }

        if (in_array($request->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
            $paymentId = null;
            $customer = $this->sessionHelper->getCustomer();
            $billingAddress = $this->sessionHelper->getBillingAddress($customer);
            $paymentMethod = $this->sessionHelper->getPaymentMethod($customer);

            if (PaymentMethods::exists($paymentMethod)) {
                $profileConfig = $this->profileConfigService->getProfileConfig(
                    $billingAddress->getCountry()->getIso(),
                    $this->context->getShop()->getId(),
                    false,
                    PaymentMethods::isZeroPercentInstallment($paymentMethod)
                );

                $data = [
                    'sandbox' => $profileConfig->isSandbox(),
                ];

                //if no DF token is set, receive all the necessary data to set it and extend template
                if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                    // create id and write it to the session
                    $data['dfp']['token'] = $this->dfpService->getDfpId();
                    $data['dfp']['snippetId'] = $this->configService->getDfpSnippetId();
                }
                if($view->getAssign('ratepay')) {
                    $data = array_merge($view->getAssign('ratepay'), $data);
                }
                $view->assign('ratepay', $data);


                if(PaymentMethods::isInstallment($paymentMethod)) {

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
        } else if ($request->getActionName() === 'confirm') {
            $error = $request->getParam('rpay_message');
            if($error) {
                $view->assign('ratepayMessage', $error);
            }
        }
    }
}
