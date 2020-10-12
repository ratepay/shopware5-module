<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;

use Doctrine\ORM\Query\Expr;
use RpayRatePay\Component\Service\Logger;

class UpdateTransactionsSubscriber implements \Enlight\Event\SubscriberInterface
{
    const JOB_NAME = 'Shopware_Cronjob_UpdateRatepayTransactions';

    const MSG_NOTIFY_UPDATES_TO_RATEPAY = '[%d/%d] Processing order %d ...notify needed updates to Ratepay';

    /**
     * @var string
     */
    protected $__cronjobLastExecutionDate;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            self::JOB_NAME => 'updateRatepayTransactions',
            'UpdateRatepayTransactions' => 'updateRatepayTransactions',
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
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        if (!$this->hasBiDirectionalityActivated($config)) {
            Logger::singleton()->info('Ratepay bidirectionality is turned off.');
            return 'Ratepay bidirectionality is turned off.';
        }

        try {
            $orderIds = $this->findCandidateOrdersForUpdate($config);
            $totalOrders = count($orderIds);
            $orderProcessor = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler();
            foreach ($orderIds as $key => $orderId) {
                /* @var \Shopware\Models\Order\Order $order */
                $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
                Logger::singleton()->info(
                    sprintf(self::MSG_NOTIFY_UPDATES_TO_RATEPAY, ($key + 1), $totalOrders, $orderId)
                );
                $orderProcessor->informRatepayOfOrderStatusChange($order);
            }
        } catch (\Exception $e) {
            Logger::singleton()->error(
                sprintf('Fehler UpdateTransactionsSubscriber: %s %s', $e->getMessage(), $e->getTraceAsString())
            );
            return $e->getMessage();
        }

        return 'Success';
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getLastUpdateDate()
    {
        if (empty($this->_cronjobLastExecutionDate)) {
            $query = 'SELECT `next`, `interval` FROM s_crontab WHERE `action` = ?';
            $row = Shopware()->Db()->fetchRow($query, [self::JOB_NAME]);

            $date = new \DateTime($row['next']);
            $date->sub(new \DateInterval('PT' . $row['interval'] . 'S'));

            $this->_cronjobLastExecutionDate = $date;
        }

        return $this->_cronjobLastExecutionDate;
    }

    /**
     * @param $config
     * @return array
     * @throws \Exception
     */
    private function findCandidateOrdersForUpdate($config)
    {
        $allowedOrderStates = [
            $config->RatePayFullDelivery,
            $config->RatePayFullCancellation,
            $config->RatePayFullReturn,
        ];
        $paymentMethods = $this->getAllowedPaymentMethods();
        $changeDate = $this->getChangeDateLimit();

        $query = Shopware()->Db()->select()
            ->from(['history' => 's_order_history'], null)
            ->joinLeft(['order' => 's_order'], 'history.orderID = order.id', ['id'])
            ->joinLeft(['payment' => 's_core_paymentmeans'], 'order.paymentID = payment.id', null)
            ->where('history.change_date >= :changeDate')
            ->where('order.status IN (' . join(', ', $allowedOrderStates) . ')')
            ->where('payment.name IN (' . join(', ', $paymentMethods) . ')')
            ->distinct(true);

        $rows = Shopware()->Db()->fetchAll($query, [':changeDate' => $changeDate]);

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
     */
    private function getAllowedPaymentMethods()
    {
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        $quotedPaymentMethods = array_map(function ($method) {
            return "'" . $method . "'";
        }, $paymentMethods);

        return $quotedPaymentMethods;
    }

    /**
     * @param $config
     * @return bool
     */
    private function hasBiDirectionalityActivated($config)
    {
        return (bool)$config->get('RatePayBidirectional');
    }
}
