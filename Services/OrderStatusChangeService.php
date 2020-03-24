<?php

namespace RpayRatePay\Services;

use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Request\PaymentCancelService;
use RpayRatePay\Services\Request\PaymentDeliverService;
use RpayRatePay\Services\Request\PaymentReturnService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class OrderStatusChangeService
{
    const MSG_FAILED_SENDING_FULL_RETURN = 'Unable to send full return for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_CANCELLATION = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_DELIVERY = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FULL_DELIVERY_REJECTED = 'Full delivery request was rejected for order: %d. %s';
    const MSG_FULL_RETURN_REJECTED = 'Full return request was rejected for order: %d. %s';
    const MSG_FULL_CANCELLATION_REJECTED = 'Full cancellation request was rejected for order: %d. %s';


    const CHANGE_DELIVERY = 'CHANGE_DELIVERY';
    const CHANGE_RETURN = 'CHANGE_RETURN';
    const CHANGE_CANCEL = 'CHANGE_CANCEL';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var PaymentDeliverService
     */
    protected $paymentDeliverService;

    /**
     * @var PaymentCancelService
     */
    protected $paymentCancelService;

    /**
     * @var PaymentReturnService
     */
    protected $paymentReturnService;
    /**
     * @var ConfigService
     */
    protected $pluginConfig;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var PositionHelper
     */
    protected $positionHelper;

    public function __construct(
        ModelManager $modelManager,
        ConfigService $pluginConfig,
        PositionHelper $positionHelper,
        PaymentDeliverService $paymentDeliverService,
        PaymentCancelService $paymentCancelService,
        PaymentReturnService $paymentReturnService,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
        $this->positionHelper = $positionHelper;
        $this->paymentDeliverService = $paymentDeliverService;
        $this->paymentCancelService = $paymentCancelService;
        $this->paymentReturnService = $paymentReturnService;
        $this->logger = $logger;
    }

    /**
     * Sends Ratepay notification of order status change when new status meets criteria.
     *
     * @param Order $order
     */
    public function informRatepayOfOrderStatusChange(Order $order)
    {
        if (PaymentMethods::exists($order->getPayment()) === false) {
            //payment is not a ratepay order
            return;
        }

        $roundTrips = [
            self::CHANGE_DELIVERY => $this->pluginConfig->getBidirectionalOrderStatus('full_delivery'),
            self::CHANGE_RETURN => $this->pluginConfig->getBidirectionalOrderStatus('full_return'),
            self::CHANGE_CANCEL => $this->pluginConfig->getBidirectionalOrderStatus('full_cancellation'),
        ];

        foreach ($roundTrips as $changeType => $statusId) {
            if ($order->getOrderStatus()->getId() !== $statusId) {
                continue;
            }

            switch ($changeType) {
                case self::CHANGE_DELIVERY:
                    $this->performFullDeliveryRequest($order);
                    break;
                case self::CHANGE_RETURN:
                    $this->performFullReturnRequest($order);
                    break;
                case self::CHANGE_CANCEL:
                    $this->performFullCancellationRequest($order);
                    break;
            }
        }
    }

    private function performFullDeliveryRequest(Order $order)
    {
        try {
            $sendToGateway = false;
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                if ($position->getOpenQuantity() > 0) {
                    //just deliver not delivered or canceled items
                    $basketArrayBuilder->addItem($detail, $position->getOpenQuantity());
                    $sendToGateway = true;
                }
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                //just deliver not delivered or canceled items
                if ($shippingPosition->getOpenQuantity() === 1) {
                    $basketArrayBuilder->addShippingItem();
                    $sendToGateway = true;
                }
            }
            if ($sendToGateway == false) {
                return;
            }

            $this->paymentDeliverService->setItems($basketArrayBuilder);
            $this->paymentDeliverService->setOrder($order);
            $result = $this->paymentDeliverService->doRequest();

            if ($result->isSuccessful() === false) {
                $this->logger->warning(sprintf(self::MSG_FULL_DELIVERY_REJECTED, $order->getId()));
            }

        } catch (Exception $e) {
            $this->logger->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_DELIVERY, $order->getId(), $e->getMessage())
            );
        }
    }

    private function performFullCancellationRequest(Order $order)
    {
        try {
            $sendToGateway = false;
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                // openQuantity should be the orderedQuantity.
                // To prevent unexpected errors we will only submit the openQuantity
                if ($position->getOpenQuantity() > 0) {
                    $basketArrayBuilder->addItem($detail, $position->getOpenQuantity());
                    $sendToGateway = true;
                }
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                // openQuantity should be the orderedQuantity.
                // To prevent unexpected errors we will only submit the openQuantity
                if ($shippingPosition->getOpenQuantity() === 1) {
                    $basketArrayBuilder->addShippingItem();
                    $sendToGateway = true;
                }
            }

            if ($sendToGateway == false) {
                return;
            }

            $this->paymentCancelService->setItems($basketArrayBuilder);
            $this->paymentCancelService->setOrder($order);
            $result = $this->paymentCancelService->doRequest();

            if ($result->isSuccessful() === false) {
                $this->logger->warning(sprintf(self::MSG_FULL_CANCELLATION_REJECTED, $order->getId()));
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_CANCELLATION, $order->getId(), $e->getMessage())
            );
        }
    }

    private function performFullReturnRequest(Order $order)
    {
        try {
            $sendToGateway = false;
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                //to prevent unexpected errors, we will only return the delivered items
                if ($position->getDelivered() - $position->getReturned()) {
                    $basketArrayBuilder->addItem($detail, $position->getDelivered() - $position->getReturned());
                    $sendToGateway = true;
                }
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                if ($shippingPosition->getOpenQuantity() === 0) { // shipping has been delivered so we can return it
                    $basketArrayBuilder->addShippingItem();
                    $sendToGateway = true;
                }
            }
            if ($sendToGateway == false) {
                return;
            }

            $this->paymentReturnService->setItems($basketArrayBuilder);
            $this->paymentReturnService->setOrder($order);
            $result = $this->paymentReturnService->doRequest();

            if ($result->isSuccessful() === false) {
                $this->logger->warning(sprintf(self::MSG_FULL_RETURN_REJECTED, $order->getId()));
            }

        } catch (Exception $e) {
            $this->logger->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_RETURN, $order->getId(), $e->getMessage())
            );
        }
    }
}
