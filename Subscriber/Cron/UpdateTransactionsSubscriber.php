<?php

namespace RpayRatePay\Subscriber\Cron;

use \Enlight\Event\SubscriberInterface;
use Monolog\Logger;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class UpdateTransactionsSubscriber implements SubscriberInterface
{
    const JOB_NAME = 'Shopware_CronJob_RatePay_UpdateTransactions';

    const MSG_NOTIFY_UPDATES_TO_RATEPAY = '[%d/%d] Processing order %d ...notify needed updates to RatePAY';

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
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;
    /**
     * @var Logger
     */
    protected $logger;


    public function __construct(
        ModelManager $modelManager,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            self::JOB_NAME => 'updateRatepayTransactions',
        ];
    }

    /**
     * EventListener for frontend controller
     *
     * @param \Shopware_Components_Cron_CronJob $job
     *
     * @return string
     * @throws \Exception
     */
    public function updateRatepayTransactions(\Shopware_Components_Cron_CronJob $job)
    {
        if ($this->configService->isBidirectionalEnabled() === false) {
            $this->logger->info('RatePAY bidirectionality is turned off.');
            return 'RatePAY bidirectionality is turned off.';
        }

        try {
            $orderIds = $this->findCandidateOrdersForUpdate();
            $totalOrders = count($orderIds);
            //TODO service
            $orderProcessor = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler();
            foreach ($orderIds as $key => $orderId) {
                $order = $this->modelManager->find(Order::class, $orderId);
                $this->logger->info(
                    sprintf(self::MSG_NOTIFY_UPDATES_TO_RATEPAY, ($key + 1), $totalOrders, $orderId)
                );
                $orderProcessor->informRatepayOfOrderStatusChange($order);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Fehler UpdateTransactionsSubscriber: %s %s', $e->getMessage(), $e->getTraceAsString())
            );
            return $e->getMessage();
        }

        return 'Success';
    }



    /**
     * @param $config
     * @return array
     * @throws \Exception
     */
    private function findCandidateOrdersForUpdate()
    {
        $allowedOrderStates = [
            $this->configService->getConfig('ratepay/bidirectional/status/full_delivery'),
            $this->configService->getConfig('ratepay/bidirectional/status/full_cancellation'),
            $this->configService->getConfig('ratepay/bidirectional/status/full_return'),
        ];
        $changeDate = $this->getChangeDateLimit();

        $query = $this->db->select()
            ->from(['history' => 's_order_history'], null)
            ->joinLeft(['order' => 's_order'], 'history.orderID = order.id', ['id'])
            ->joinLeft(['payment' => 's_core_paymentmeans'], 'order.paymentID = payment.id', null)
            ->where('history.change_date >= :changeDate')
            ->where('order.status IN (:allowed_orderstatus)')
            ->where('payment.name IN (:payment_methods)')
            ->distinct(true);

        $rows = $this->db->fetchAll(
            $query,
            [
                ':changeDate' => $changeDate,
                ':allowed_orderstatus' => $allowedOrderStates,
                ':payment_methods' => PaymentMethods::getNames()
            ]);

        return array_column($rows, 'id');
    }

    /**
     * Gets the bottom limits to fetch order updates.
     * This is important to keep a well performing process, due to
     * an unknown amount of orders could take a long of time.
     *
     * @return string
     * @throws \Exception
     */
    private function getChangeDateLimit()
    {
        $date = $this->getLastUpdateDate();
        if (empty($date)) {
            $date = new \DateTime();
        }

        $date->sub(new \DateInterval('PT1H'));
        $changeDate = $date->format('Y-m-d H:i:s');

        return $changeDate;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getLastUpdateDate()
    {
        if (empty($this->_cronjobLastExecutionDate)) {
            $query = 'SELECT `next`, `interval` FROM s_crontab WHERE `action` = ?';
            $row = $this->db->fetchRow($query, [self::JOB_NAME]);

            $date = new \DateTime($row['next']);
            $date->sub(new \DateInterval('PT' . $row['interval'] . 'S'));

            $this->_cronjobLastExecutionDate = $date;
        }

        return $this->_cronjobLastExecutionDate;
    }

}
