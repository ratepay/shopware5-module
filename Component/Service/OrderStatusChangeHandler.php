<?php

class Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler
{
    const MSG_ARTICLE_WAS_SENT = 'Artikel wurde versand.';

    const MSG_ARTICLE_WAS_CANCELLED = 'Artikel wurde storniert.';

    const MSG_ARTICLE_WAS_RETURNED = 'Artikel wurde retourniert.';

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullShipped(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullDelivery'])) {
            return false;
        }
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if (!in_array($order->getPayment()->getName(), $paymentMethods)) {
            return false;
        }

        $query = 'SELECT count(*) as ct FROM s_order o
                LEFT JOIN s_order_details od ON od.orderID = o.id
                LEFT JOIN rpay_ratepay_order_positions rop ON rop.s_order_details_id = od.id
                WHERE o.id = ?
                AND (rop.delivered != 0 OR rop.cancelled != 0 OR rop.returned != 0)';

        $count = Shopware()->Db()->fetchOne($query, [$order->getId()]);

        return 0 === (int)$count;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullCancellation(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullCancellation'])) {
            return false;
        }
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if (!in_array($order->getPayment()->getName(), $paymentMethods)) {
            return false;
        }

        $query = 'SELECT count(*) FROM s_order o
                LEFT JOIN s_order_details od ON od.orderID = o.id
                LEFT JOIN rpay_ratepay_order_positions rop ON rop.s_order_details_id = od.id
                WHERE o.id = ?
                AND (rop.delivered !=0 OR rop.cancelled != 0 OR rop.returned != 0)';

        $count = Shopware()->Db()->fetchOne($query, [$order->getId()]);

        return 0 === (int)$count;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullReturn(\Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullReturn'])) {
            return false;
        }
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if (!in_array($order->getPayment()->getName(), $paymentMethods)) {
            return false;
        }

        $query = 'SELECT count(*) FROM s_order o
                LEFT JOIN s_order_details od ON od.orderID = o.id
                LEFT JOIN rpay_ratepay_order_positions rop ON rop.s_order_details_id = od.id
                WHERE o.id = ?
                AND (rop.delivered = 0 OR rop.cancelled != 0 OR rop.returned != 0)';

        $count = Shopware()->Db()->fetchOne($query, [$order->getId()]);

        return 0 === (int)$count;
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
        $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices);
        $history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();

        $shippingCosts = $order->getInvoiceShipping();

        $items = [];
        foreach ($order->getDetails() as $item) {
            $items[] = $this->getItemForOrderDetails($item);
        }

        if (!empty($shippingCosts)) {
            $items['Shipping'] = [
                'articlename' => 'Shipping',
                'ordernumber' => 'shipping',
                'quantity' => 1,
                'priceNumeric' => $shippingCosts,
                'tax_rate' => $order->getDetails()->first()->getTaxRate(),
            ];
        }

        if ($this->mustSendFullShipped($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callConfirmationDeliver($operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = [
                        'delivered' => $item['quantity']
                    ];

                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), self::MSG_ARTICLE_WAS_SENT, $item['articlename'], $item['ordernumber'], $item['quantity']);
                }
            }
        }

        if ($this->mustSendFullCancellation($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callPaymentChange($operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = [
                        'cancelled' => $item['quantity']
                    ];
                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), self::MSG_ARTICLE_WAS_CANCELLED, $item['articlename'], $item['ordernumber'], $item['quantity']);
                }
            }
        }

        if ($this->mustSendFullReturn($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'return';
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callPaymentChange($operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = [
                        'returned' => $item['quantity']
                    ];
                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), self::MSG_ARTICLE_WAS_RETURNED, $item['articlename'], $item['ordernumber'], $item['quantity']);
                }
            }
        }
    }

    /**
     * Updates the given binding for the given article
     *
     * @param $orderID
     * @param $articleOrderNumber
     * @param $bind
     * @throws \Zend_Db_Adapter_Exception
     */
    private function updateItem($orderID, $articleOrderNumber, $bind)
    {
        if ($articleOrderNumber === 'shipping') {
            Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
        } else {
            $positionId = Shopware()->Db()->fetchOne('SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?', [$orderID, $articleOrderNumber]);
            Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $positionId);
        }
    }

    /**
     * @param $item
     * @return array
     */
    private function getItemForOrderDetails($item)
    {
        return [
            'articlename' => $item->getArticlename(),
            'ordernumber' => $item->getArticlenumber(),
            'quantity' => $item->getQuantity(),
            'priceNumeric' => $item->getPrice(),
            'tax_rate' => $item->getTaxRate(),
        ];
    }
}
