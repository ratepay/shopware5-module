<?php

class Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler
{
    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullShipped(Shopware\Models\Order\Order $order, $config)
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

        $count = Shopware()->Db()->fetchOne($query, array($order->getId()));

        return (int)$count === 0;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullCancellation(Shopware\Models\Order\Order $order, $config)
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

        $count = Shopware()->Db()->fetchOne($query, array($order->getId()));

        return (int)$count === 0;

    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @param $config
     * @return bool
     */
    private function mustSendFullReturn(Shopware\Models\Order\Order $order, $config)
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

        $count = Shopware()->Db()->fetchOne($query, array($order->getId()));

        return (int)$count === 0;
    }

    /**
     * Sends Ratepay notification of order status change when new status meets criteria.
     *
     * @param \Shopware\Models\Order\Order $order
     * @throws Zend_Db_Adapter_Exception
     */
    public function informRatepayOfOrderStatusChange(Shopware\Models\Order\Order $order)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        $attributes = $order->getAttribute();
        $backend = (bool)($attributes->getRatepayBackend());

        $netPrices = $order->getNet() === 1;
        $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices);
        $history      = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();

        $shippingCosts = $order->getInvoiceShipping();

        $items = array();
        $i = 0;
        foreach ($order->getDetails() as $item) {
            $items[$i]['articlename'] = $item->getArticlename();
            $items[$i]['ordernumber'] = $item->getArticlenumber();
            $items[$i]['quantity'] = $item->getQuantity();
            $items[$i]['priceNumeric'] = $item->getPrice();
            $items[$i]['tax_rate'] = $item->getTaxRate();
            $taxRate = $item->getTaxRate();
            $i++;
        }
        if (!empty($shippingCosts)) {
            $items['Shipping']['articlename'] = 'Shipping';
            $items['Shipping']['ordernumber'] = 'shipping';
            $items['Shipping']['quantity'] = 1;
            $items['Shipping']['priceNumeric'] = $shippingCosts;
            $items['Shipping']['tax_rate'] = $taxRate;
        }

        if ($this->mustSendFullShipped($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callRequest('ConfirmationDeliver', $operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = array(
                        'delivered' => $item['quantity']
                    );

                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), "Artikel wurde versand.", $item['articlename'], $item['ordernumber'], $item['quantity']);
                }
            }
        }

        if ($this->mustSendFullCancellation($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callRequest('PaymentChange', $operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = array(
                        'cancelled' => $item['quantity']
                    );
                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), "Artikel wurde storniert.", $item['articlename'], $item['ordernumber'], $item['quantity']);
                }
            }
        }

        if ($this->mustSendFullReturn($order, $config)) {
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'return';
            $modelFactory->setOrderId($order->getNumber());
            $result = $modelFactory->callRequest('PaymentChange', $operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = array(
                        'returned' => $item['quantity']
                    );
                    $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                    if ($item['quantity'] <= 0) {
                        continue;
                    }
                    $history->logHistory($order->getId(), "Artikel wurde retourniert.", $item['articlename'], $item['ordernumber'], $item['quantity']);
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
     * @throws Zend_Db_Adapter_Exception
     */
    private function updateItem($orderID, $articleOrderNumber, $bind)
    {
        if ($articleOrderNumber === 'shipping') {
            Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
        } else {
            $positionId = Shopware()->Db()->fetchOne("SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?", array($orderID, $articleOrderNumber));
            Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $positionId);
        }
    }

}