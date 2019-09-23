<?php

namespace RpayRatePay\Services;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\Position\Product as ProductPosition;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Request\PaymentCancelService;
use RpayRatePay\Services\Request\PaymentDeliverService;
use RpayRatePay\Services\Request\PaymentReturnService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail as OrderDetail;
use Shopware\Models\Order\Order;

class OrderStatusChangeService
{
    const MSG_FAILED_SENDING_FULL_RETURN = 'Unable to send full return for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_CANCELLATION = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_DELIVERY = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FULL_DELIVERY_REJECTED = 'Full delivery request was rejected for order: %d. %s';
    const MSG_FULL_RETURN_REJECTED = 'Full return request was rejected for order: %d. %s';
    const MSG_FULL_CANCELLATION_REJECTED = 'Full cancellation request was rejected for order: %d. %s';

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


        if ($this->canSendFullDelivery($order)) {
            $this->performFullDeliveryRequest($order);
        }

        if ($this->canSendFullCancellation($order)) {
            $this->performFullCancellationRequest($order);
        }

        if ($this->canSendFullReturn($order)) {
            $this->performFullReturnRequest($order);
        }
    }

    private function performFullReturnRequest(Order $order)
    {
        $this->logger->debug('--> canSendFullReturn');
        try {
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                //to prevent unexpected errors, we will only return the delivered items
                $basketArrayBuilder->addItem($detail, $position->getDelivered());
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                if ($shippingPosition->getOpenQuantity() === 0) { // shipping has been delivered so we can return it
                    $basketArrayBuilder->addShippingItem();
                }
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

    private function performFullCancellationRequest(Order $order)
    {
        $this->logger->debug('--> canSendFullCancellation');

        try {
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                // openQuantity should be the orderedQuantity.
                // To prevent unexpected errors we will only submit the openQuantity
                $basketArrayBuilder->addItem($detail, $position->getOpenQuantity());
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                // openQuantity should be the orderedQuantity.
                // To prevent unexpected errors we will only submit the openQuantity
                if ($shippingPosition->getOpenQuantity() === 1) {
                    $basketArrayBuilder->addShippingItem();
                }
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

    private function performFullDeliveryRequest(Order $order)
    {
        $this->logger->debug('--> canSendFullDelivery');

        try {
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($order->getDetails() as $detail) {
                $position = $this->positionHelper->getPositionForDetail($detail);
                if ($position->getOpenQuantity() > 0) {
                    //just deliver not delivered or canceled items
                    $basketArrayBuilder->addItem($detail, $position->getOpenQuantity());
                }
            }
            $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
            if ($shippingPosition) {
                //just deliver not delivered or canceled items
                if ($shippingPosition->getOpenQuantity() === 1) {
                    $basketArrayBuilder->addShippingItem();
                }
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


    /**
     * @param Order $order
     * @return bool
     */
    private function canSendFullDelivery(Order $order)
    {
        if ($order->getOrderStatus()->getId() !== $this->pluginConfig->getBidirectionalOrderStatus('full_delivery')) {
            return false;
        }

        $qb = $this->getCountQueryBuilder($order);
        $qb->andWhere($qb->expr()->gt('(detail.quantity - position.delivered - position.cancelled)', '0'));

        try {
            return intval($qb->getQuery()->getSingleScalarResult()) > 0;
        } catch (NoResultException $e) {
            return false;
        }

    }

    /**
     * @param Order $order
     * @return bool
     */
    private function canSendFullCancellation(Order $order)
    {
        if ($order->getOrderStatus()->getId() !== $this->pluginConfig->getBidirectionalOrderStatus('full_cancellation')) {
            return false;
        }

        $qb = $this->getCountQueryBuilder($order);
        //only if no product has been shipped/canceled/returned.
        $qb->andWhere($qb->expr()->eq('position.delivered + position.cancelled + position.returned', 0));

        try {
            return intval($qb->getQuery()->getSingleScalarResult()) > 0;
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function canSendFullReturn(Order $order)
    {
        if ($order->getOrderStatus()->getId() !== $this->pluginConfig->getBidirectionalOrderStatus('full_return')) {
            return false;
        }

        $qb = $this->getCountQueryBuilder($order);
        $qb->andWhere($qb->expr()->eq('position.delivered', 'detail.quantity'));

        try {
            return intval($qb->getQuery()->getSingleScalarResult()) > 0;
        } catch (NoResultException $e) {
            return false;
        }
    }

    protected function getCountQueryBuilder(Order $order)
    {
        $qb = $this->modelManager->createQueryBuilder();
        return $qb->select('count(detail.id)')                                          //TODO add discount & shipping
        ->from(ProductPosition::class, 'position')
            ->innerJoin(OrderDetail::class, 'detail', Join::WITH, 'position.orderDetail = detail.id')
            ->andWhere($qb->expr()->eq('detail.order', ':order_id'))
            ->andWhere($qb->expr()->in('detail.mode', PositionHelper::MODE_SW_PRODUCT)) //TODO add discount & shipping
            ->setParameter('order_id', $order->getId());
    }
}
