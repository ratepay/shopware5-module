<?php

namespace RpayRatePay\Subscriber\Backend;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Enlight_Hook_HookArgs;
use Monolog\Logger;
use RatePAY\Service\Math;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\HelperService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;

use \Enlight\Event\SubscriberInterface;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;

class OrderOperationsSubscriber implements SubscriberInterface
{
    /**
     * @var \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler
     */
    private $orderStatusChangeHandler;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var ConfigService
     */
    protected $config;
    /**
     * @var HelperService
     */
    protected $helperService;
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        HelperService $helperService,
        ConfigService $config,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->helperService = $helperService;
        $this->config = $config;
        $this->orderStatusChangeHandler = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler();
        $this->logger = $logger;
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
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    public function beforeSaveOrderInBackend(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $order = $this->modelManager->find(Order::class, $request->getParam('id'));
        $newPaymentMethod = $this->modelManager->find(Payment::class, $request->getParam('paymentId'));

        $paymentMethods = PaymentMethods::getNames();

        if ((!in_array($order->getPayment()->getName(), $paymentMethods) && in_array($newPaymentMethod->getName(), $paymentMethods))
            || (in_array($order->getPayment()->getName(), $paymentMethods) && $newPaymentMethod->getName() != $order->getPayment()->getName())
        ) {
            $this->logger->addNotice('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
            $arguments->stop();
            throw new \Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
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

        /* @var \Shopware\Models\Order\Order $order */
        $order = $this->modelManager->find(Order::class, $parameter['id']);
        if ($this->helperService->isRatePayPayment($order) && $this->config->isBidirectionalEnabled()) {
            $this->orderStatusChangeHandler->informRatepayOfOrderStatusChange($order);
        }
    }

    /**
     * Handler for saving in the batch processing dialog box for orders.
     *
     * @param Enlight_Hook_HookArgs $arguments
     */
    public function afterOrderBatchProcess(Enlight_Hook_HookArgs $arguments)
    {
        if (!$this->config->isBidirectionalEnabled()) {
            return;
        }

        $request = $arguments->getSubject()->Request();
        $orders = $request->getParam('orders');

        if (count($orders) < 1) {
            throw new \Exception('No order selected');
        }

        foreach ($orders as $order) {
            /* @var \Shopware\Models\Order\Order $order */
            $order = $this->modelManager->find(Order::class, $order['id']);
            if (!$this->helperService->isRatePayPayment($order)) {
                continue;
            }

            $this->orderStatusChangeHandler->informRatepayOfOrderStatusChange($order);
        }
    }

    /**
     * Stops Order deletion, when its not permitted
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     */
    public function beforeDeleteOrderPosition(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        $order = $this->modelManager->find(Order::class, $parameter['orderID']);
        if ($parameter['valid'] != true && $this->helperService->isRatePayPayment($order)) {
            $this->logger->warning('Positionen einer RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden. Bitte Stornieren Sie die Artikel in der Artikelverwaltung.');
            $arguments->stop();
        }

        return true;
    }

    /**
     * Stops Order deletion, when any article has been send
     *
     * @param Enlight_Hook_HookArgs $arguments
     * @return bool
     */
    public function beforeDeleteOrder(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();
        if (PaymentMethods::exists($parameter['payment'][0]['name']) === false) {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM `s_order_details` AS `detail` '
            . 'INNER JOIN `rpay_ratepay_order_positions` AS `position` '
            . 'ON `position`.`s_order_details_id` = `detail`.`id` '
            . 'WHERE `detail`.`orderID`=? AND '
            . '(`position`.`delivered` > 0 OR `position`.`cancelled` > 0 OR `position`.`returned` > 0)';
        $count = $this->db->fetchOne($sql, [$parameter['id']]);
        if ($count > 0) {
            $this->logger->warning('RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden, wenn sie bereits bearbeitet worden sind.');
            $arguments->stop();
        } else {
            $order = $this->modelManager->find('Shopware\Models\Order\Order', $parameter['id']);

            $sqlShipping = 'SELECT invoice_shipping, invoice_shipping_net, invoice_shipping_tax_rate FROM s_order WHERE id = ?';
            $shippingCosts = $this->db->fetchRow($sqlShipping, [$parameter['id']]);

            $items = [];
            $i = 0;
            /** @var Detail $item */
            foreach ($order->getDetails() as $item) {
                $items[$i]['articlename'] = $item->getArticlename();
                $items[$i]['ordernumber'] = $item->getArticlenumber();
                $items[$i]['quantity'] = $item->getQuantity();
                $items[$i]['priceNumeric'] = $item->getPrice();
                $items[$i]['tax_rate'] = $item->getTaxRate();
                $taxRate = $item->getTaxRate();

                // Shopware does have a bug - so the tax_rate might be the wrong value.
                // Issue: https://issues.shopware.com/issues/SW-24119
                $taxRate = $item->getTax() == null ? 0 : $taxRate; // this is a little fix
                $items[$i]['tax_rate'] = $taxRate;

                $i++;
            }
            if (!empty($shippingCosts)) {
                $items['Shipping']['articlename'] = 'Shipping';
                $items['Shipping']['ordernumber'] = 'shipping';
                $items['Shipping']['quantity'] = 1;
                $items['Shipping']['priceNumeric'] = $shippingCosts['invoice_shipping'];

                // Shopware does have a bug - so the tax_rate might be the wrong value.
                // Issue: https://issues.shopware.com/issues/SW-24119
                // we can not simple calculate the shipping tax cause the values in the database are not properly rounded.
                // So we do not get the correct shipping tax rate if we calculate it.
                $calculatedTaxRate = TaxHelper::taxFromPrices(floatval($shippingCosts['invoice_shipping_net']), floatval($shippingCosts['invoice_shipping']));
                $shippingTaxRate = $calculatedTaxRate > 0 ? $shippingCosts['invoice_shipping_tax_rate'] : 0;

                $items['Shipping']['tax_rate'] = $shippingTaxRate;
            }

            $attributes = $order->getAttribute();
            $backend = (bool)($attributes->getRatepayBackend());

            $netPrices = $order->getNet() === 1;

            $modelFactory = new ModelFactory(null, $backend, $netPrices); // TODO service
            $modelFactory->setTransactionId($parameter['transactionId']);
            $modelFactory->setTransactionId($order->getTransactionID());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $result = $modelFactory->callPaymentChange($operationData);

            if ($result !== true) {
                $this->logger->warning('Bestellung k&ouml;nnte nicht gelöscht werden, da die Stornierung bei RatePAY fehlgeschlagen ist.');
                $arguments->stop();
            }
        }
    }
}
