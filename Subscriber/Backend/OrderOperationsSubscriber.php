<?php

namespace RpayRatePay\Subscriber\Backend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Enlight_Hook_HookArgs;
use Exception;
use Monolog\Logger;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\HelperService;
use RpayRatePay\Services\OrderStatusChangeService;
use RpayRatePay\Services\Request\PaymentCancelService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Backend_Order;

class OrderOperationsSubscriber implements SubscriberInterface
{
    /**
     * @var OrderStatusChangeService
     */
    private $orderStatusChangeService;
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
    /**
     * @var PaymentCancelService
     */
    protected $paymentCancelService;

    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        HelperService $helperService,
        ConfigService $config,
        Logger $logger,
        OrderStatusChangeService $orderStatusChangeService,
        PaymentCancelService $paymentCancelService
    )
    {
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->helperService = $helperService;
        $this->config = $config;
        $this->orderStatusChangeService = $orderStatusChangeService;
        $this->paymentCancelService = $paymentCancelService;

        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Order::saveAction::before' => 'beforeSaveOrderInBackend',
            'Shopware_Controllers_Backend_Order::saveAction::after' => 'onBidirectionalSendOrderOperation',
            'Shopware_Controllers_Backend_Order::batchProcessAction::after' => 'afterOrderBatchProcess',
            'Shopware_Controllers_Backend_Order::deletePositionAction::before' => 'beforeDeleteOrderPosition',
            'Shopware_Controllers_Backend_Order::deleteAction::replace' => 'replaceDeleteOrder',
        ];
    }


    /**
     * Checks if the payment method is a ratepay method. If it is a ratepay method, throw an exception
     * and forbid to change the payment method
     *
     * @param Enlight_Hook_HookArgs $args
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function beforeSaveOrderInBackend(Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $orderId = $request->getParam('id');

        $order = $this->modelManager->find(Order::class, $orderId);
        $newPaymentMethod = $this->modelManager->find(Payment::class, $request->getParam('paymentId'));

        //prevent change payment method
        if (PaymentMethods::exists($order->getPayment()) && $order->getPayment()->getId() != $newPaymentMethod->getId()) {
            $this->logger->addNotice('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
            $args->stop();
            throw new Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
        }

        return false;
    }

    /**
     * Event fired when user saves order.
     * @param Enlight_Hook_HookArgs $args
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function onBidirectionalSendOrderOperation(Enlight_Hook_HookArgs $args)
    {
        if (!$this->config->isBidirectionalEnabled()) {
            return;
        }
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $orderId = $controller->Request()->getParam('id');

        /* @var Order $order */
        $order = $this->modelManager->find(Order::class, $orderId);

        $this->orderStatusChangeService->informRatepayOfOrderStatusChange($order);
    }

    /**
     * Handler for saving in the batch processing dialog box for orders.
     *
     * @param Enlight_Hook_HookArgs $args
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function afterOrderBatchProcess(Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $orders = $controller->Request()->getParam('orders', []);
        $singleOrderId = $controller->Request()->getParam('id', null);
        if (count($orders) < 1 && empty($singleOrderId)) {
            return;
        }

        if(count($orders) == 0) {
            $orders = [['id' => $singleOrderId]];
        }

        foreach ($orders as $order) {
            /* @var Order $order */
            $order = $this->modelManager->find(Order::class, $order['id']);
            $this->orderStatusChangeService->informRatepayOfOrderStatusChange($order);
        }
    }

    /**
     * Stops Order deletion, when its not permitted
     *
     * @param Enlight_Hook_HookArgs $args
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function beforeDeleteOrderPosition(Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $orderId = $controller->Request()->getParam('orderID');

        $order = $this->modelManager->find(Order::class, $orderId);
        if ($controller->Request()->get('valid') != true && $this->helperService->isRatePayPayment($order)) {
            $this->logger->warning('Positionen einer RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden. Bitte Stornieren Sie die Artikel in der Artikelverwaltung.');
            $args->stop();
        }

        return true;
    }

    /**
     * Stops Order deletion, when any article has been send
     *
     * @param Enlight_Hook_HookArgs $args
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function replaceDeleteOrder(Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();

        $order = $this->modelManager->find(Order::class, $request->getParam('id'));

        if (PaymentMethods::exists($order->getPayment()) === false) {
            //payment is not a ratepay order
            return;
        }
        $sql = 'SELECT COUNT(*) FROM `s_order_details` AS `detail` '
            . 'INNER JOIN `rpay_ratepay_order_positions` AS `position` '
            . 'ON `position`.`s_order_details_id` = `detail`.`id` '
            . 'WHERE `detail`.`orderID`=? AND '
            . '(`position`.`delivered` > 0 OR `position`.`cancelled` > 0 OR `position`.`returned` > 0)';
        $count = $this->db->fetchOne($sql, [$order->getId()]);
        if ($count > 0) {
            $message = 'RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden, wenn sie bereits bearbeitet worden sind.';
            $controller->View()->assign(['success' => false, 'message' => $message]);
            $this->logger->warning($message);
        } else {

            $basketBuilder = new BasketArrayBuilder($order, $order->getDetails());
            $basketBuilder->addShippingItem();

            $this->paymentCancelService->setItems($basketBuilder);
            $this->paymentCancelService->setOrder($order);
            $response = $this->paymentCancelService->doRequest();

            if ($response->isSuccessful()) {
                $args->getSubject()->executeParent($args->getMethod(), $args->getArgs());
            } else {
                $message = 'Bestellung k&ouml;nnte nicht gelöscht werden, da die Stornierung bei RatePAY fehlgeschlagen ist.';
                $controller->View()->assign(['success' => false, 'message' => $message]);
                $this->logger->warning($message);
            }

        }
    }
}
