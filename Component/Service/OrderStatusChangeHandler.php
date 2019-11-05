<?php

use RpayRatePay\Component\Service\RatepayHelper as Helper;
use RpayRatePay\Component\Service\Logger;

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

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function canSendFullDelivery(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullDelivery'])
            || !Helper::isRatePayPayment($order->getPayment()->getName())) {
            return false;
        }

        $query = Shopware()->Db()->select()
            ->from(['position' => 'rpay_ratepay_order_positions'], ['total' => 'COUNT(*)'])
            ->joinLeft(['detail' => 's_order_details'], 'position.s_order_details_id = detail.id', null)
            ->where('detail.orderID = :orderId')
            ->where('(position.delivered + position.cancelled + position.returned) > 0');

        $count = (int)Shopware()->Db()->fetchOne($query, [':orderId' => $order->getId()]);
        if ($count > 0) {
            Logger::singleton()->warning(
                sprintf('-> Order [%d] has %d "not deliverable" positions', $order->getId(), $count)
            );
        }


        return $count === 0;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function canSendFullCancellation(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullCancellation'])
            || !Helper::isRatePayPayment($order->getPayment()->getName())) {
            return false;
        }

        $query = Shopware()->Db()->select()
            ->from(['position' => 'rpay_ratepay_order_positions'], ['total' => 'COUNT(*)'])
            ->joinLeft(['detail' => 's_order_details'], 'position.s_order_details_id = detail.id', null)
            ->where('detail.orderID = :orderId')
            ->where('(position.delivered + position.cancelled + position.returned) > 0');

        $count = (int)Shopware()->Db()->fetchOne($query, [':orderId' => $order->getId()]);
        if ($count > 0) {
            Logger::singleton()->warning(
                sprintf('-> Order [%d] has %d "not cancellable" positions', $order->getId(), $count)
            );
        }

        return $count === 0;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function canSendFullReturn(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullReturn'])
            || !Helper::isRatePayPayment($order->getPayment()->getName())) {
            return false;
        }

        $query = Shopware()->Db()->select()
            ->from(['position' => 'rpay_ratepay_order_positions'], ['total' => 'COUNT(*)'])
            ->joinLeft(['detail' => 's_order_details'], 'position.s_order_details_id = detail.id', null)
            ->where('detail.orderID = :orderId')
            ->where('position.delivered != detail.quantity');

        $count = (int)Shopware()->Db()->fetchOne($query, [':orderId' => $order->getId()]);
        if ($count > 0) {
            Logger::singleton()->warning(
                sprintf('-> Order [%d] has %d "not returnable" positions', $order->getId(), $count)
            );
        }

        return $count === 0;
    }

    /**
     * Sends Ratepay notification of order status change when new status meets criteria.
     *
     * @param \Shopware\Models\Order\Order $order
     * @throws \Zend_Db_Adapter_Exception
     */
    public function informRatepayOfOrderStatusChange(\Shopware\Models\Order\Order $order)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        $attributes = $order->getAttribute();
        $backend = (bool)($attributes->getRatepayBackend());

        $netPrices = $order->getNet() === 1;
        $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices, $order->getShop());

        $shippingCosts = $order->getInvoiceShipping();

        $items = [];
        foreach ($order->getDetails() as $item) {
            $items[] = $this->getItemForOrderDetails($item);
        }

        if (!empty($shippingCosts)) {
            $items['Shipping'] = [
                'articlename' => 'Shipping',
                'orderDetailId' => 'shipping',
                'ordernumber' => 'shipping',
                'quantity' => 1,
                'priceNumeric' => $shippingCosts,

                // Shopware does have a bug - so getTaxRate will not work properly
                // Issue: https://issues.shopware.com/issues/SW-24119
                // we can not simple calculate the shipping tax cause the values in the database are not properly rounded.
                // So we do not get the correct shipping tax rate if we calculate it.
                'tax_rate' => \RatePAY\Service\Math::taxFromPrices($order->getInvoiceShippingNet(), $order->getInvoiceShipping()) > 0 ? $order->getInvoiceShippingTaxRate() : 0
            ];
        }

        if ($this->canSendFullDelivery($order, $config)) {
            $this->performFullDeliveryRequest($order, $modelFactory, $items);
        }

        if ($this->canSendFullCancellation($order, $config)) {
            $this->performFullCancellationRequest($order, $modelFactory, $items);
        }

        if ($this->canSendFullReturn($order, $config)) {
            $this->performFullReturnRequest($order, $modelFactory, $items);
        }
    }

    /**
     * Updates the given binding for the given article
     *
     * @param $orderID
     * @param $articleOrderNumberOrId
     * @param $bind
     * @throws \Zend_Db_Adapter_Exception
     */
    private function updateItem($orderID, $articleOrderNumberOrId, $bind)
    {
        if ($articleOrderNumberOrId === 'shipping') {
            Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
        } else if ($articleOrderNumberOrId === 'discount') {
            Shopware()->Db()->update('rpay_ratepay_order_discount', $bind, '`s_order_id`=' . $orderID);
        } else {
            Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $articleOrderNumberOrId);
        }
    }

    /**
     * @param \Shopware\Models\Order\Detail $item
     * @return array
     */
    private function getItemForOrderDetails($item)
    {
        $data = [
            'articlename' => $item->getArticlename(),
            'orderDetailId' => $item->getId(),
            'ordernumber' => $item->getArticlenumber(),
            'quantity' => $item->getQuantity(),
            'priceNumeric' => $item->getPrice(),
            // Shopware does have a bug - so getTaxRate will not work properly
            // Issue: https://issues.shopware.com/issues/SW-24119
            'tax_rate' => $item->getTax() == null ? 0 : $item->getTaxRate()
        ];
        return Shopware()->Events()->filter('RatePAY_filter_order_items', $data);
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $state
     * @param $items
     * @param $history
     * @param $historyMsg
     * @throws Zend_Db_Adapter_Exception
     */
    private function updateItemStates(\Shopware\Models\Order\Order $order, $state, $items, $history, $historyMsg)
    {
        if (!in_array($state, ['delivered', 'returned', 'cancelled'])) {
            Logger::singleton()->error('Incorrect item state "' . $state . '" was given.');
            return;
        }

        foreach ($items as $item) {
            $bind = [
                $state => $item['quantity']
            ];

            $this->updateItem($order->getId(), $item['orderDetailId'], $bind);
            if ($item['quantity'] <= 0) {
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
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $modelFactory
     * @param $items
     */
    private function performFullReturnRequest(\Shopware\Models\Order\Order $order, $modelFactory, $items)
    {
        Logger::singleton()->debug('--> canSendFullReturn');
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

    private function performFullCancellationRequest(\Shopware\Models\Order\Order $order, $modelFactory, $items)
    {
        Logger::singleton()->debug('--> canSendFullCancellation');
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
     * @param \Shopware\Models\Order\Order $order
     * @param $modelFactory
     * @param $items
     */
    private function performFullDeliveryRequest(\Shopware\Models\Order\Order $order, $modelFactory, $items)
    {
        Logger::singleton()->debug('--> canSendFullDelivery');
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
