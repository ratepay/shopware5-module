<?php

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Service\Logger;
use Shopware\Models\Order\Order;

class OrderOperationsSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler
     */
    private $orderStatusChangeHandler;

    public function __construct()
    {
        $this->orderStatusChangeHandler = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler();
    }

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
     * and forbid to change the payment method
     *
     * @param \Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    public function beforeSaveOrderInBackend(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $request->getParam('id'));
        $newPaymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $request->getParam('paymentId'));
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if ((!in_array($order->getPayment()->getName(), $paymentMethods) && in_array($newPaymentMethod->getName(), $paymentMethods))
            || (in_array($order->getPayment()->getName(), $paymentMethods) && $newPaymentMethod->getName() != $order->getPayment()->getName())
        ) {
            Logger::singleton()->addNotice('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
            $arguments->stop();
            throw new \Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
        }

        return false;
    }

    /**
     * Event fired when user saves order.
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Zend_Db_Adapter_Exception
     */
    public function onBidirectionalSendOrderOperation(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();

        /* @var \Shopware\Models\Order\Order $order */
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);
        if ($this->usesBidirectionalUpdates($order->getPayment()->getName())) {
            $this->orderStatusChangeHandler->informRatepayOfOrderStatusChange($order);
        }
    }

    /**
     * Handler for saving in the batch processing dialog box for orders.
     *
     * @param \Enlight_Hook_HookArgs $arguments
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Exception
     */
    public function afterOrderBatchProcess(\Enlight_Hook_HookArgs $arguments)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
        if (!$config->get('RatePayBidirectional')) {
            return;
        }
        $controller = $arguments->getSubject();

        $orders = $controller->Request()->getParam('orders', []);
        $singleOrderId = $controller->Request()->getParam('id', null);

        if (count($orders) < 1 && empty($singleOrderId)) {
            return;
        }

        if (count($orders) == 0) {
            $orders = [['id' => $singleOrderId]];
        }

        foreach ($orders as $order) {
            /* @var \Shopware\Models\Order\Order $order */
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $order['id']);
            $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
            if (!in_array($order->getPayment()->getName(), $paymentMethods)) {
                continue;
            }

            $this->orderStatusChangeHandler->informRatepayOfOrderStatusChange($order);
        }
    }

    /**
     * Stops Order deletion, when its not permitted
     *
     * @param \Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function beforeDeleteOrderPosition(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['orderID']);
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if ($parameter['valid'] != true && in_array($order->getPayment()->getName(), $paymentMethods)) {
            Logger::singleton()->warning('Positionen einer RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden. Bitte Stornieren Sie die Artikel in der Artikelverwaltung.');
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
    public function beforeDeleteOrder(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        if (!in_array($parameter['payment'][0]['name'], $paymentMethods)) {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM `s_order_details` AS `detail` '
            . 'INNER JOIN `rpay_ratepay_order_positions` AS `position` '
            . 'ON `position`.`s_order_details_id` = `detail`.`id` '
            . 'WHERE `detail`.`orderID`=? AND '
            . '(`position`.`delivered` > 0 OR `position`.`cancelled` > 0 OR `position`.`returned` > 0)';
        $count = Shopware()->Db()->fetchOne($sql, [$parameter['id']]);
        if ($count > 0) {
            Logger::singleton()->warning('RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden, wenn sie bereits bearbeitet worden sind.');
            $arguments->stop();
        } else {
            /** @var Order $order */
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

            $sqlShipping = 'SELECT invoice_shipping FROM s_order WHERE id = ?';
            $shippingCosts = Shopware()->Db()->fetchOne($sqlShipping, [$parameter['id']]);

            $items = [];
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

            $attributes = $order->getAttribute();
            $backend = (bool)($attributes->getRatepayBackend());

            $netPrices = $order->getNet() === 1;

            $modelFactory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices, $order->getShop());
            $modelFactory->setTransactionId($parameter['transactionId']);
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $result = $modelFactory->callPaymentChange($operationData);

            if ($result !== true) {
                Logger::singleton()->warning('Bestellung k&ouml;nnte nicht gelöscht werden, da die Stornierung bei RatePAY fehlgeschlagen ist.');
                $arguments->stop();
            }
        }
    }

    /**
     * @param $paymentName
     * @return bool
     */
    public function usesBidirectionalUpdates($paymentName)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();

        return $config->get('RatePayBidirectional') && in_array($paymentName, $paymentMethods);
    }
}
