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
use RpayRatePay\Services\InstallmentService;
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
    /**
     * @var InstallmentService
     */
    private $installmentService;

    public function __construct(
        ModelManager $modelManager,
        SessionHelper $sessionHelper,
        ContextService $contextService,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        InstallmentService $installmentService,
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
        $this->installmentService = $installmentService;
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

        if(//!$request->isDispatched() ||
            $response->isException() ||
            !$view->hasTemplate() ||
            $request->getModuleName() != 'frontend' ||
            $request->getControllerName() != 'checkout' ||
            $view->getAssign('sPayment') == null
        ) {
            return;
        }

        if ($request->getActionName() === 'confirm') {
            $paymentMethod = $this->sessionHelper->getPaymentMethod();
            if(PaymentMethods::isInstallment($paymentMethod) == false) {
                return;
            }
            $data = [];

            //TODO if no DF token is set, receive all the necessary data to set it and extend template
            if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                // create id and write it to the session
                $data['dfp']['token'] = $this->dfpService->getDfpId();
                $data['dfp']['snippetId'] = $this->configService->getDfpSnippetId();
            }

            if(PaymentMethods::isInstallment($paymentMethod)) {
                $billingAddress = $this->sessionHelper->getBillingAddress();
                $calcInput = $this->sessionHelper->getData('installment_calculator_input');
                $installmentPlanHtml = $this->installmentService->getInstallmentPlanTemplate(
                    $billingAddress->getCountry()->getIso(),
                    Shopware()->Shop()->getId(),
                    $paymentMethod,
                    false,
                    $this->sessionHelper->getInstallmentRequestDTO()
                );
                $data['installmentPlan'] = $installmentPlanHtml;
            }

            $view->assign('ratepay', $data);
            $error = $request->getParam('rpay_message');
            if($error) {
                $view->assign('ratepayMessage', $error);
            }
        }
    }
}
