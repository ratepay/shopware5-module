<?php

use RpayRatePay\Component\Service\Logger;
use RpayRatePay\Component\Service\RatepayHelper as Helper;
use RpayRatePay\Models\IPosition;
use RpayRatePay\Models\OrderDiscount;
use RpayRatePay\Models\OrderPositions;
use RpayRatePay\Models\OrderShipping;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler
{
    const MSG_ARTICLE_WAS_SENT = 'Artikel wurde versand.';
    const MSG_ARTICLE_WAS_CANCELLED = 'Artikel wurde storniert.';
    const MSG_ARTICLE_WAS_RETURNED = 'Artikel wurde retourniert.';
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
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
        $this->db = Shopware()->Db();
    }

    /**
     * Sends Ratepay notification of order status change when new status meets criteria.
     *
     * @param Order $order
     * @throws \Zend_Db_Adapter_Exception
     */
    public function informRatepayOfOrderStatusChange(Order $order)
    {
        if (!Helper::isRatePayPayment($order->getPayment()->getName())) {
            return;
        }

        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        $roundTrips = [
            self::CHANGE_DELIVERY => $config['RatePayFullDelivery'],
            self::CHANGE_RETURN => $config['RatePayFullReturn'],
            self::CHANGE_CANCEL => $config['RatePayFullCancellation'],
        ];

        foreach ($roundTrips as $changeType => $statusId) {
            if ($order->getOrderStatus()->getId() !== $statusId) {
                continue;
            }

            $attributes = $order->getAttribute();
            $backend = (bool)($attributes->getRatepayBackend());

            $netPrices = $order->getNet() === 1;
            $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices, $order->getShop());

            $shippingCosts = $order->getInvoiceShipping();

            $items = [];
            foreach ($order->getDetails() as $item) {
                $item = $this->getItemForOrderDetails($item, $changeType);
                if ($item) {
                    $items[] = $item;
                }
            }

            $shippingPosition = $this->modelManager->find(OrderShipping::class, $order->getId());

            if ($shippingPosition && !empty($shippingCosts)) {
                $quantity = $this->getQuantity($shippingPosition, 1, $changeType);
                if ($quantity) {
                    $items['Shipping'] = [
                        'articlename' => 'Shipping',
                        'ordernumber' => 'shipping',
                        'quantity' => $quantity,
                        'priceNumeric' => $shippingCosts,
                        // Shopware does have a bug - so getTaxRate will not work properly
                        // Issue: https://issues.shopware.com/issues/SW-24119
                        // we can not simple calculate the shipping tax cause the values in the database are not properly rounded.
                        // So we do not get the correct shipping tax rate if we calculate it.
                        'tax_rate' => \RatePAY\Service\Math::taxFromPrices($order->getInvoiceShippingNet(), $order->getInvoiceShipping()) > 0 ? $order->getInvoiceShippingTaxRate() : 0,
                        'positionObject' => $shippingPosition
                    ];
                }
            }

            if(count($items) === 0) {
                continue;
            }

            switch ($changeType) {
                case self::CHANGE_DELIVERY:
                    $this->performFullDeliveryRequest($order, $modelFactory, $items);
                    break;
                case self::CHANGE_CANCEL:
                    $this->performFullCancellationRequest($order, $modelFactory, $items);
                    break;
                case self::CHANGE_RETURN:
                    $this->performFullReturnRequest($order, $modelFactory, $items);
                    break;
            }
        }
    }

    protected function getQuantity(IPosition $position, $orderedQuantity, $changeType)
    {
        switch ($changeType) {
            case self::CHANGE_DELIVERY:
            case self::CHANGE_CANCEL:
                return $orderedQuantity - $position->getDelivered() - $position->getCancelled();
                break;
            case self::CHANGE_RETURN:
                return $position->getDelivered() - $position->getReturned();
                break;
            default:
                return 0;
        }
    }

    private function getItemForOrderDetails(Detail $item, $changeType)
    {
        $position = $this->modelManager->find(OrderPositions::class, $item->getId());
        if ($position === null) {
            $position = $this->modelManager->find(OrderDiscount::class, [
                'sOrderId' => $item->getOrder()->getId(),
                'sOrderDetailId' => $item->getId()
            ]);
        }
        if ($position === null) {
            return null;
        }
        $quantity = $this->getQuantity($position, $item->getQuantity(), $changeType);
        $data = $quantity > 0 ? [
            'articlename' => $item->getArticlename(),
            'orderDetailId' => $item->getId(),
            'ordernumber' => $item->getArticlenumber(),
            'quantity' => $quantity,
            'priceNumeric' => $item->getPrice(),
            // Shopware does have a bug - so getTaxRate will not work properly
            // Issue: https://issues.shopware.com/issues/SW-24119
            'tax_rate' => $item->getTax() == null ? 0 : $item->getTaxRate(),
            'modus' => $item->getMode(),
            'positionObject' => $position
        ] : null;
        return $data ? Shopware()->Events()->filter('RatePAY_filter_order_items', $data) : null;
    }

    /**
     * @param Order $order
     * @param $state
     * @param $items
     * @param $history
     * @param $historyMsg
     * @throws Zend_Db_Adapter_Exception
     */
    private function updateItemStates(Order $order, $state, $items, $history, $historyMsg)
    {
        if (!in_array($state, ['delivered', 'returned', 'cancelled'])) {
            Logger::singleton()->error('Incorrect item state "' . $state . '" was given.');
            return;
        }

        foreach ($items as $item) {

            /** @var IPosition $position */
            $position = isset($item['positionObject']) ? $item['positionObject'] : null;
            if ($position == null || $item['quantity'] <= 0) {
                continue;
            }

            switch ($state) {
                case 'delivered':
                    $position->setDelivered($position->getDelivered() + $item['quantity']);
                    break;
                case 'returned':
                    $position->setReturned($position->getReturned() + $item['quantity']);
                    break;
                case 'cancelled':
                    $position->setCancelled($position->getCancelled() + $item['quantity']);
                    break;
                default:
                    continue;
            }

            $history->logHistory(
                $order->getId(),
                $historyMsg,
                $item['articlename'],
                $item['ordernumber'],
                $item['quantity']
            );
        }
        $this->modelManager->flush(array_map(function ($item) {
            return $item['positionObject'];
        }, $items));
    }

    /**
     * @param Order $order
     * @param $modelFactory
     * @param $items
     */
    private function performFullReturnRequest(Order $order, $modelFactory, $items)
    {
        $history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();
        $operationData = [
            'orderId' => $order->getId(),
            'items' => $items,
            'subtype' => 'return',
        ];

        try {
            $modelFactory->setTransactionId($order->getTransactionID());
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callPaymentChange($operationData);
            if ($result === true) {
                $this->updateItemStates($order, 'returned', $items, $history, self::MSG_ARTICLE_WAS_RETURNED);
                return;
            }

            Logger::singleton()->warning(sprintf(self::MSG_FULL_RETURN_REJECTED, $order->getId()));
        } catch (\Exception $e) {
            Logger::singleton()->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_RETURN, $order->getId(), $e->getMessage())
            );
        }
    }

    private function performFullCancellationRequest(Order $order, $modelFactory, $items)
    {
        $history = new \Shopware_Plugins_Frontend_RpayRatePay_Component_History();
        $operationData = [
            'orderId' => $order->getId(),
            'items' => $items,
            'subtype' => 'cancellation',
        ];

        try {
            $modelFactory->setTransactionId($order->getTransactionID());
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callPaymentChange($operationData);
            if ($result === true) {
                $this->updateItemStates($order, 'cancelled', $items, $history, self::MSG_ARTICLE_WAS_CANCELLED);
                return;
            }

            Logger::singleton()->warning(sprintf(self::MSG_FULL_CANCELLATION_REJECTED, $order->getId()));
        } catch (\Exception $e) {
            Logger::singleton()->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_CANCELLATION, $order->getId(), $e->getMessage())
            );
        }
    }

    /**
     * @param Order $order
     * @param $modelFactory
     * @param $items
     */
    private function performFullDeliveryRequest(Order $order, $modelFactory, $items)
    {
        $history = new \Shopware_Plugins_Frontend_RpayRatePay_Component_History();
        $operationData = [
            'orderId' => $order->getId(),
            'items' => $items,
        ];

        try {
            $modelFactory->setTransactionId($order->getTransactionID());
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callConfirmationDeliver($operationData);
            if ($result === true) {
                $this->updateItemStates($order, 'delivered', $items, $history, self::MSG_ARTICLE_WAS_SENT);
                return;
            }

            Logger::singleton()->warning(sprintf(self::MSG_FULL_DELIVERY_REJECTED, $order->getId()));
        } catch (\Exception $e) {
            Logger::singleton()->error(
                sprintf(self::MSG_FAILED_SENDING_FULL_DELIVERY, $order->getId(), $e->getMessage())
            );
        }
    }
}
