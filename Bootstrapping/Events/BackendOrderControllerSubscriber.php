<?php

use RpayRatePay\Component\Mapper\PaymentRequestData;

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

            $orderStruct = $orderHydrator->hydrateFromRequest($request);

            //first find out if it's a ratepay order
            $paymentType = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $orderStruct->getPaymentId());
            $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $orderStruct->getCustomerId());
            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($customer, $paymentType);

            if (!$validation->isRatePAYPayment()) {
                Shopware()->Pluginlogger()->info('Not a ratepay payment. Forwarding to to SWAGBackendOrder');
                $this->forwardToSWAGBackendOrders($hookArgs);
            } else {
                Shopware()->Pluginlogger()->info('Got a ratepay order');

                $swagValidations = $this->runSwagValidations($orderStruct);
                if($swagValidations->getMessages()) {
                    $this->fail($view, $swagValidations->getMessages());
                    return;
                }

                Shopware()->Pluginlogger()->info('SWAG Backend Order Passed');

                $paymentRequestData = $this->orderStructToPaymentRequestData($orderStruct, $paymentType, $customer);

                $paymentRequester = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, true);

                Shopware()->Pluginlogger()->info('Calling payment request.');

                $answer = $paymentRequester->callPaymentRequest($paymentRequestData);
                if ($answer->isSuccessful()) {
                    Shopware()->Pluginlogger()->info('Payment Request success!');
                    $transactionId = $answer->getTransactionId();
                    $this->forwardToSWAGBackendOrders($hookArgs);

                    $orderId = $view->getAssign("orderId");
                    Shopware()->Pluginlogger()->info('Order created with id ' . $orderId);

                } else {
                    Shopware()->Pluginlogger()->info('Payment Request rejected!');
                    $customerMessage = $answer->getCustomerMessage();
                    $this->fail($view, [$customerMessage]);
                }
            }
        } catch(\Exception $e) {
            Shopware()->Pluginlogger()->error($e->getMessage());
            Shopware()->Pluginlogger()->error($e->getTraceAsString());
            $view->assign([
                'success' => false,
                'violations' => [$e->getMessage()]
            ]);
        }
    }

    private function orderStructToPaymentRequestData(\SwagBackendOrder\Components\Order\Struct\OrderStruct $orderStruct,
                                                     \Shopware\Models\Payment\Payment $paymentType,
                                                     \Shopware\Models\Customer\Customer $customer)
    {
        $method = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::getPaymentMethod(
            $paymentType->getName()
        );

        $billing = Shopware()->Models()->find('Shopware\Models\Customer\Address', $orderStruct->getBillingAddressId());

        $shipping = Shopware()->Models()->find('Shopware\Models\Customer\Address', $orderStruct->getShippingAddressId());

        $items = [];
        foreach($orderStruct->getPositions() as $positionStruct) {
            $items[] = $this->positionStructToArray($positionStruct);
        }

        $shippingCost = $orderStruct->getShippingCosts();

        $shippingTax = $orderStruct->getShippingCostsNet() - $orderStruct->getSHippingCostsNet();

        $dfpToken = '';

        $shop = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $orderStruct->getLanguageShopId());
        $localeLang =  $shop->getLocale()->getLocale();
        $lang = substr($localeLang, 0, 2);

        $amount = $orderStruct->getTotal();

        return new PaymentRequestData($method, $customer, $billing, $shipping, $items, $shippingCost, $shippingTax, $dfpToken, $lang, $amount);
    }

    private function positionStructToArray(SwagBackendOrder\Components\Order\Struct\PositionStruct $item)
    {
        $a = [
            'articlename' => $item->getName(),
            'ordernumber' => $item->getNumber(), //should be article number, see BasketArrayBuilder
            'quantity' => $item->getQuantity(),
            'priceNumeric' => $item->getPrice(), //testen zu sehen ob price gross
            'tax_rate' => $item->getTaxRate(),
        ];

        return $a;
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

    private function fail($view, $messages)
    {
        $view->assign([
            'success' => false,
            'violations' => $messages,
        ]);
    }

    private function runSwagValidations($orderStruct)
    {
            $validator = Shopware()->Container()->get('swag_backend_order.order.order_validator');
            $violations = $validator ->validate($orderStruct);
            return $violations;
    }
}