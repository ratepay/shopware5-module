<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Cron;

use DateInterval;
use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Exception;
use Monolog\Logger;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Exception\RatepayException;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\OrderStatusChangeService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware_Components_Cron_CronJob;

class UpdateTransactionsSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    protected $_cronjobLastExecutionDate;
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var OrderStatusChangeService
     */
    private $orderStatusChangeService;


    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        OrderStatusChangeService $orderStatusChangeService,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->configService = $configService;
        $this->orderStatusChangeService = $orderStatusChangeService;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_RatePay_UpdateTransactions' => 'updateRatepayTransactions',
            'Shopware_CronJob_Ratepay_OrderPositionWatcher' => 'watchOrderDetails'
        ];
    }

    public function watchOrderDetails(\Enlight_Event_EventArgs $args)
    {
        $orderRepo = $this->modelManager->getRepository(Order::class);

        $qb = $orderRepo->createQueryBuilder('o');
        $qb->addSelect('detail')
            ->innerJoin('o.details', 'detail', Join::WITH)
            ->innerJoin('o.payment', 'paymentMethod', Join::WITH)
            ->innerJoin('detail.attribute', 'detailAttribute', Join::WITH, $qb->expr()->neq('detailAttribute.ratepayLastStatus', 'detail.status'))
            ->andWhere($qb->expr()->in('paymentMethod.name', ':methods'))
            ->setParameter('methods', PaymentMethods::getNames());

        /*$qb = $orderRepo->createQueryBuilder('detail');
        $qb->innerJoin('detail.order', 'o', Join::WITH)
            ->innerJoin('detail.attribute', 'attribute', Join::WITH)
            ->innerJoin('o.payment', 'paymentMethod', Join::WITH)
            ->andWhere($qb->expr()->neq('attribute.ratepayLastStatus', 'detail.status'))
            ->andWhere($qb->expr()->in('paymentMethod.name', ':methods'))
            ->setParameter('methods', PaymentMethods::getNames());*/

        $query = $qb->getQuery();

        $attributesToFlush = [];
        /** @var Order $order */
        foreach ($query->getResult() as $order) {
            $candidates = $order->getDetails()->toArray();
            $this->orderStatusChangeService->informRatepayOfOrderPositionStatusChange($order, $candidates);

            // at least sync the detail statuses
            /** @var Detail $detail */
            foreach ($candidates as $detail) {
                $detail->getAttribute()->setRatepayLastStatus($detail->getStatus()->getId());
                $attributesToFlush[] = $detail->getAttribute();
            }
        }
        $this->modelManager->flush($attributesToFlush);
    }

    /**
     * EventListener for frontend controller
     *
     * @param Shopware_Components_Cron_CronJob $job
     *
     * @return string
     * @throws Exception
     */
    public function updateRatepayTransactions(Shopware_Components_Cron_CronJob $job)
    {
        if ($this->configService->isBidirectionalEnabled() === false) {
            $this->logger->info('Ratepay bidirectionality is turned off.');
            return 'Ratepay bidirectionality is turned off.';
        }
        try {
            $orderIds = $this->findCandidateOrdersForUpdate();
            $totalOrders = count($orderIds);
            foreach ($orderIds as $key => $orderId) {
                $order = $this->modelManager->find(Order::class, $orderId);
                $this->logger->info(
                    sprintf('Bidirectionality: Processing %d/%d order-id %d ...', ($key + 1), $totalOrders, $orderId),
                    [
                        'order_id' => $order->getId(),
                        'order_number' => $order->getNumber()
                    ]
                );
                try {
                    $this->orderStatusChangeService->informRatepayOfOrderStatusChange($order);
                } catch (Exception $e) {
                    $context = [
                        'order_id' => $order->getId(),
                        'order_number' => $order->getNumber(),
                        'trace' => $e->getTraceAsString()
                    ];
                    if ($e instanceof RatepayException) {
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $context = array_merge($context, $e->getContext());
                    }
                    $this->logger->error('Bidirectionality: ' . $e->getMessage(), $context);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('bidirectionality: ' .$e->getMessage(), [$e->getTraceAsString()]);
            return $e->getMessage();
        }
        return 'Success';
    }


    /**
     * @param $config
     * @return array
     * @throws Exception
     */
    private function findCandidateOrdersForUpdate()
    {
        $allowedOrderStates = [
            // these are global configurations
            $this->configService->getBidirectionalOrderStatus('full_delivery'),
            $this->configService->getBidirectionalOrderStatus('full_cancellation'),
            $this->configService->getBidirectionalOrderStatus('full_return'),
        ];
        foreach ($allowedOrderStates as $i => $allowedOrderState) {
            if ($allowedOrderState == null || empty($allowedOrderState) || is_numeric($allowedOrderState) == false) {
                unset($allowedOrderStates[$i]);
            }
        }

        $changeDate = $this->getChangeDateLimit();
        $paymentMethods = PaymentMethods::getNames();

        $query = $this->db->select()
            ->distinct(true)
            ->from(['history' => 's_order_history'], null)
            ->joinLeft(['s_order' => 's_order'], 'history.orderID = s_order.id', ['id'])
            ->joinLeft(['payment' => 's_core_paymentmeans'], 's_order.paymentID = payment.id', null)
            ->where('history.change_date >= :changeDate')
            ->where("s_order.status IN (" . implode(",", $allowedOrderStates) . ")")
            ->where("payment.name IN ('" . implode("','", $paymentMethods) . "')")
            ->bind([
                'changeDate' => $changeDate
            ]);

        $rows = $this->db->fetchAll($query);
        return array_column($rows, 'id');
    }

    /**
     * Gets the bottom limits to fetch order updates.
     * This is important to keep a well performing process, due to
     * an unknown amount of orders could take a long of time.
     *
     * @return string
     * @throws Exception
     */
    private function getChangeDateLimit()
    {
        $date = $this->getLastUpdateDate();
        if (empty($date)) {
            $date = new DateTime();
        }

        $date->sub(new DateInterval('PT1H'));
        $changeDate = $date->format('Y-m-d H:i:s');

        return $changeDate;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function getLastUpdateDate()
    {
        if (empty($this->_cronjobLastExecutionDate)) {
            $query = 'SELECT `next`, `interval` FROM s_crontab WHERE `action` = ?';
            $row = $this->db->fetchRow($query, ['Shopware_CronJob_RatePay_UpdateTransactions']);

            $date = new DateTime($row['next']);
            $date->sub(new DateInterval('PT' . $row['interval'] . 'S'));

            $this->_cronjobLastExecutionDate = $date;
        }
        return $this->_cronjobLastExecutionDate;
    }

}
