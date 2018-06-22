<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:23
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderOperationsSubscriber implements \Enlight\Event\SubscriberInterface
{
    const PAYMENT_METHODS = array(
        'rpayratepayinvoice',
        'rpayratepayrate',
        'rpayratepaydebit',
        'rpayratepayrate0',
    );

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Order::saveAction::before' => 'beforeSaveOrderInBackend',
            'Shopware_Controllers_Backend_Order::saveAction::after' => 'onBidirectionalSendOrderOperation',
            'Shopware_Controllers_Backend_Order::batchProcessAction::after' => 'afterOrderBatchProcess',
            'Shopware_Controllers_Backend_Order::deletePositionAction::before' => 'beforeDeleteOrderPosition',
            'Shopware_Controllers_Backend_Order::deleteAction::before' => 'beforeDeleteOrder',
        ];
    }

    /**
     * Checks if the payment method is a ratepay method. If it is a ratepay method, throw an exception
     * and forbit to change the payment method
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function beforeSaveOrderInBackend(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $request->getParam('id'));
        $newPaymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $request->getParam('paymentId'));

        if ((!in_array($order->getPayment()->getName(), self::PAYMENT_METHODS) && in_array($newPaymentMethod->getName(), self::PAYMENT_METHODS))
            || (in_array($order->getPayment()->getName(), self::PAYMENT_METHODS) && $newPaymentMethod->getName() != $order->getPayment()->getName())
        ) {
            Shopware()->Pluginlogger()->addNotice('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
            $arguments->stop();
            throw new Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
        }

        return false;
    }

    /**
     * Event fired when user saves order.
     * 
     * @param Enlight_Hook_HookArgs $arguments
     */
    public function onBidirectionalSendOrderOperation(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

        if (!$config->get('RatePayBidirectional') ||
            !in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)
        ) {
            return;
        }

        $this->informRatepayOfOrderStatusChange($order);

    }

    /**
     * Handler for saving in the batch processing dialog box for orders.
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @throws Exception
     */
    public function afterOrderBatchProcess(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();

        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        if (!$config->get('RatePayBidirectional')) {
            return;
        }

        $orders = $request->getParam('orders');

        if (count($orders) < 1) {
            throw new Exception('No order selected');
        }

        foreach ($orders as $order) {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $order['id']);

            if (!in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)) {
                continue;
            }

            $this->informRatepayOfOrderStatusChange($order);
        }
    }


    private function mustSendFullShipped(Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullDelivery'])) {
            return false;
        }

        if (!in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)) {
            return false;
        }

        $query = 'SELECT count(*) FROM s_order o
                LEFT JOIN s_order_details od ON od.orderID = o.id
                LEFT JOIN rpay_ratepay_order_positions rop ON rop.s_order_details_id = od.id
                WHERE o.id = ?
                AND (rop.delivered != 0 OR rop.cancelled != 0 OR rop.returned != 0)';

        $count = Shopware()->Db()->fetchOne($query, array($order->getId()));

        return (int)$count === 0;
    }

    private function mustSendFullCancellation(Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullCancellation'])) {
            return false;
        }

        if (!in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)) {
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

    private function mustSendFullReturn(Shopware\Models\Order\Order $order, $config)
    {
        if ($order->getOrderStatus()->getId() !== (int)($config['RatePayFullReturn'])) {
            return false;
        }

        if (!in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)) {
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
     */
    public function informRatepayOfOrderStatusChange(Shopware\Models\Order\Order $order)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
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

    /**
     * Stops Order deletion, when its not permitted
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function beforeDeleteOrderPosition(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['orderID']);
        if ($parameter['valid'] != true && in_array($order->getPayment()->getName(), self::PAYMENT_METHODS)) {
            Shopware()->Pluginlogger()->warning('Positionen einer RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden. Bitte Stornieren Sie die Artikel in der Artikelverwaltung.');
            $arguments->stop();
        }

        return true;
    }

    /**
     * Stops Order deletion, when any article has been send
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function beforeDeleteOrder(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        if (!in_array($parameter['payment'][0]['name'], self::PAYMENT_METHODS)) {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM `s_order_details` AS `detail` "
            . "INNER JOIN `rpay_ratepay_order_positions` AS `position` "
            . "ON `position`.`s_order_details_id` = `detail`.`id` "
            . "WHERE `detail`.`orderID`=? AND "
            . "(`position`.`delivered` > 0 OR `position`.`cancelled` > 0 OR `position`.`returned` > 0)";
        $count = Shopware()->Db()->fetchOne($sql, array($parameter['id']));
        if ($count > 0) {
            Shopware()->Pluginlogger()->warning('RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden, wenn sie bereits bearbeitet worden sind.');
            $arguments->stop();
        }
        else {
            $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

            //get country of order
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $order->getCustomer()->getBilling()->getCountryId());

            $sqlShipping = "SELECT invoice_shipping FROM s_order WHERE id = ?";
            $shippingCosts = Shopware()->Db()->fetchOne($sqlShipping, array($parameter['id']));

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

            $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            $modelFactory->setTransactionId($parameter['transactionId']);
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $result = $modelFactory->callRequest('PaymentChange', $operationData);

            if ($result !== true) {
                Shopware()->Pluginlogger()->warning('Bestellung k&ouml;nnte nicht gelöscht werden, da die Stornierung bei RatePAY fehlgeschlagen ist.');
                $arguments->stop();
            }
        }
    }
}