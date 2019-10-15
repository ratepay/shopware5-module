<?php

namespace RpayRatePay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use RatePAY\Service\DeviceFingerprint;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\InstallmentService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Model\ModelManager;
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

        if (//!$request->isDispatched() ||
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
            if (PaymentMethods::isInstallment($paymentMethod) == false) {
                return;
            }
            $data = [];

            if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                $dfpHelper = new DeviceFingerprint($this->configService->getDfpSnippetId());
                $data['dfp'] = str_replace('\\"', '"', $dfpHelper->getDeviceIdentSnippet($this->dfpService->getDfpId()));
            }
            $this->dfpService->deleteDfpId();

            if (PaymentMethods::isInstallment($paymentMethod)) {
                $billingAddress = $this->sessionHelper->getBillingAddress();
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
            if ($error) {
                $view->assign('ratepayMessage', $error);
            }
        }
    }
}
