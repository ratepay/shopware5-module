<?php

namespace RpayRatePay\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use RpayRatePay\Service\PaymentProcessorService;
use RpayRatePay\Services\HelperService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class OrderDetailsProcessSubscriber implements SubscriberInterface
{

    /**
     * @var HelperService
     */
    protected $helperService;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        ModelManager $modelManager,
        HelperService $helperService,
        $arg4
    )
    {
        $this->modelManager = $modelManager;
        $this->helperService = $helperService;
    }

    public static function getSubscribedEvents()
    {
        return [
            //this event got notified when a order is saved to the database after the order details got saved.
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'insertRatepayPositions',
        ];
    }

    public function insertRatepayPositions(Enlight_Event_EventArgs $args)
    {
        $orderId = $args->get('orderId');
        /** @var Order $order */
        $order = $this->modelManager->getRepository(Order::class)->find($orderId);

        if ($this->helperService->isRatePayPayment($order)) {
            //TODO
            //$this->paymentProcessor->insertProductPositions($order);
        }
    }
}
