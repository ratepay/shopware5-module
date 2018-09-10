<?php

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Service\Logger;

class UpdateTransactionsSubscriber implements \Enlight\Event\SubscriberInterface
{
    const JOB_NAME = 'Shopware_Cronjob_UpdateRatepayTransactions';

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
     * @throws Exception
     */
    public function updateRatepayTransactions(\Shopware_Components_Cron_CronJob $job)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        if (!$config->get('RatePayBidirectional')) {
            return 'Bidrectionality is turned off.';
        }

        try {
            $orderIds = $this->findCandidateOrdersForUpdate($config);
            $orderProcessor = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_OrderStatusChangeHandler();
            foreach ($orderIds as $orderId) {
                /* @var \Shopware\Models\Order\Order $order */
                $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
                $orderProcessor->informRatepayOfOrderStatusChange($order);
            }
        } catch (\Exception $e) {
            Logger::singleton()->error('Fehler UpdateTransactionsSubscriber: ' .
                $e->getMessage() . ' ' .
                $e->getTraceAsString());
            return $e->getMessage();
        }
        return 'Success';
    }

    /**
     * @return mixed
     */
    private function getLastUpdateDate()
    {
        $query = 'SELECT `end` FROM s_crontab WHERE `action` = ?';
        return Shopware()->Db()->fetchOne($query, [self::JOB_NAME]);
    }

    /**
     * @param $config
     * @return array
     * @throws Exception
     */
    private function findCandidateOrdersForUpdate($config)
    {
        $paymentMethods = \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPaymentMethods();
        $orderStatus = [
            $config['RatePayFullDelivery'],
            $config['RatePayFullCancellation'],
            $config['RatePayFullReturn'],
        ];

        $paymentMethodsWrapped = [];
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodsWrapped[] = "'{$paymentMethod}'";
        }

        $changeDate = $this->getLastUpdateDate();

        if (empty($changeDate)) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('PT1H'));
            $changeDate = $date->format('Y-m-d H:i:s');
        }

        $query = 'SELECT o.id FROM s_order o
                INNER JOIN s_order_history oh ON oh.orderID = o.id
                LEFT JOIN s_core_paymentmeans cp ON cp.id = o.paymentID
                WHERE cp.name in (' . join(',', $paymentMethodsWrapped) . ')
                AND o.status in (' . join(',', $orderStatus) . ')
                AND oh.change_date >= :changeDate
                GROUP BY o.id';

        $rows = Shopware()->Db()->fetchAll($query, [':changeDate' => $changeDate]);

        return array_column($rows, 'id');
    }
}
