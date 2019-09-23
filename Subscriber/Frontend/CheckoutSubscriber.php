<?php

namespace RpayRatePay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\DfpService;
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
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

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
    protected $shopContextService;

    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Session_Namespace $session,
        ShopContextInterface $shopContextService,
        ConfigService $configService,
        DfpService $dfpService
    )
    {
        $this->modelManager = $modelManager;
        $this->session = $session;
        $this->shopContextService = $shopContextService;
        $this->configService = $configService;
        $this->dfpService = $dfpService;
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

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
            || !$view->hasTemplate()
        ) {
            return;
        }

        //get ratepay config based on shopId @toDo: IF DI SNIPPET ID WILL BE VARIABLE BETWEEN SUBSHOPS WE NEED TO SELECT BY SHOPID AND COUNTRY CREDENTIALS
        $pluginConfig = $this->getRatePayPluginConfig($this->shopContextService->getShop()->getId());

        $customer = $this->modelManager->find(Customer::class, $this->session->get('sUserId'));
        $paymentId = null;
        if (!is_null($this->session->get('sUserId'))) {
            $paymentId = $customer->getPaymentId();
        } elseif (!is_null($this->session->get('sPaymentID'))) { // PaymentId is set in case of new/guest customers
            $paymentId = $this->session->get('sPaymentID');
        }
        if ($paymentId == null || is_nan($paymentId)) {
            return $paymentId;
        }
        $paymentMethod = $this->modelManager->find(Payment::class, $paymentId);

        if ('checkout' === $request->getControllerName() &&
            'confirm' === $request->getActionName() &&
            $paymentMethod instanceof Payment &&
            PaymentMethods::exists($paymentMethod->getName())
        ) {
            $data = [];

            $data['sandbox'] = $pluginConfig->isSandbox();
            $userWrapped = new ShopwareCustomerWrapper($customer, $this->modelManager); //TODO service
            $data['phone'] = $userWrapped->getBilling('phone');
            //TODO add birthday ??

            //if no DF token is set, receive all the necessary data to set it and extend template
            if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                // create id and write it to the session
                $data['dfp']['token'] = $this->dfpService->getDfpId();
                $data['dfp']['snippetId'] = $this->configService->getDfpSnippetId();
            }
            $view->assign('ratepay', $data);
        }
    }


    private function getRatePayPluginConfig($shopId)
    {
        return $this->modelManager->getRepository(ProfileConfig::class)->findOneByShop($shopId);
    }
}
