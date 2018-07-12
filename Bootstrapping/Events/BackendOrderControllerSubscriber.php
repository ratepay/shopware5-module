<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:48
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_BackendOrderControllerSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_RpayRatepayBackendOrder' => 'onOrderDetailBackendController',
            'Shopware_Controllers_Backend_SwagBackendOrder::createOrderAction::replace' => 'beforeCreateOrderAction',
        ];
    }

    /**
     * Loads the Backendextentions
     *
     * @return string
     */
    public function onOrderDetailBackendController()
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/');

        return $this->path . "Controller/backend/RpayRatepayBackendOrder.php";
    }

    public function beforeCreateOrderAction(Enlight_Hook_HookArgs $hookArgs)
    {
        $request = $hookArgs->getSubject()->Request();
        $view = $hookArgs->getSubject()->View();

        try {
            Shopware()->Pluginlogger()->info('Ratepay: now making a backend payment');

            /** @var OrderHydrator $orderHydrator */
            $orderHydrator = Shopware()->Container()->get('swag_backend_order.order.order_hydrator');

            /** @var OrderValidator $orderValidator */
            $orderValidator = Shopware()->Container()->get('swag_backend_order.order.order_validator');

            $orderStruct = $orderHydrator->hydrateFromRequest($request);

            //first find out if it's a ratepay order
            $paymentType = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $orderStruct->getPaymentId());

            if(is_null($paymentType)) {
                throw new Exception("Paymenttype is null, id " . $orderStruct->getPaymentId());
            }
            $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $orderStruct->getCustomerId());
            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($customer, $paymentType);

            if (!$validation->isRatePAYPayment()) {
                Shopware()->Pluginlogger()->info('Not a ratepay payment. Forwarding to to SWAGBackendOrder');
                $this->forwardToSWAGBackendOrders($hookArgs);
            } else {
                Shopware()->Pluginlogger()->info('Got a ratepay order');

                $view->assign([
                    'success' => false,
                    'violations' => ['ratepay not yet supported. do i look like batman or what']
                ]);
                // $violations = $orderValidator->validate($orderStruct);

                /* if ($violations->getMessages()) {
                     $this->view->assign([
                         'success' => false,
                         'violations' => $violations->getMessages(),
                     ]);
                     return;
                 }*/
            }
        } catch(\Exception $e) {
            Shopware()->Pluginlogger()->info($e->getTraceAsString());
            $view->assign([
                'success' => false,
                'violations' => [$e->getMessage()]
            ]);
        }

    }

    private function forwardToSWAGBackendOrders(Enlight_Hook_HookArgs $hookArgs)
    {
        $subject = $hookArgs->getSubject();
        $parentReturn = $subject->executeParent(
            $hookArgs->getMethod(),
            $hookArgs->getArgs()
        );
        $hookArgs->setReturn($parentReturn);
    }

}