<?php

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_UpdateTransactionsSubscriber implements \Enlight\Event\SubscriberInterface
{

    const JOB_NAME = 'Shopware_Cronjob_UpdateRatepayTransactions';

    public static function getSubscribedEvents()
    {
        return [
            self::JOB_NAME => 'updateRatepayTransactions',
        ];
    }

    /**
     * Eventlistener for frontendcontroller
     *
     * @param Enlight_Event_EventArgs $arguments
     *
     * @return string
     * @throws Exception
     */
    public function updateRatepayTransactions(Enlight_Components_Cron_EventArgs $arguments)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        if (!$config->get('RatePayBidirectional')) {
            return;
        }

        try {
            $orderIds = $this->findCandiateOrdersForUpdate($config);
            $orderProcessor = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderOperationsSubscriber();
            foreach($orderIds as $orderId) {
                $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
                $orderProcessor->informRatepayOfOrderStatusChange($order);
            }
        } catch(Exception $e) {
            Shopware()->Pluginlogger()->error('Fehler UpdateTransactionsSubscriber: ' .
                $e->getMessage() . ' ' .
                $e->getTraceAsString());
        }
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
     * @return array|void
     * @throws Exception
     */
    private function findCandiateOrdersForUpdate($config)
    {
        //because we may not have any entries in s_order_history
        //and orders may take a long time
        //we limit to pretty far back in the past
        $date = new DateTime();
        $date->sub(new DateInterval('P1Y'));
        $orderDate = $date->format('Y-m-d H:i:s');

        $query = 'SELECT order_id FROM 
                    (SELECT o.id as order_id,
                     o.ordertime,
                     o.status, 
                     SUM(od.quantity) AS total_items,
                     SUM(rop.delivered) AS total_rp_shipped,
                     SUM(rop.cancelled) AS total_rp_cancelled,
                     SUM(rop.returned) AS total_rp_returned,
                     SUM(rop.returned + rop.cancelled) AS total_rp_cancelled_or_returned, 
                     COUNT(rop.s_order_details_id) AS ct_rp
                     FROM s_order o
                    LEFT JOIN s_order_details od ON od.orderID = o.id
                    LEFT JOIN s_order_history oh ON oh.orderID = o.id
                    LEFT JOIN rpay_ratepay_order_positions rop ON rop.s_order_details_id = od.id
                    GROUP BY o.id
                    ORDER BY o.id DESC) AS subquery
                WHERE ct_rp > 0
                AND (
                    (status = :fullDelivery AND total_rp_shipped = 0) OR
                    (status = :fullCancellation AND total_rp_cancelled_or_returned != total_items) OR
                    (status = :fullReturn AND total_rp_cancelled_or_returned != total_items)
                )
                AND ordertime > :orderDate';

        $rows = Shopware()->Db()->fetchAll($query, [
            ':fullDelivery' =>  $config['RatePayFullDelivery'],
            ':fullCancellation' => $config['RatePayFullCancellation'],
            ':fullReturn' => $config['RatePayFullReturn'],
            ':orderDate' => $orderDate
        ]);
        return array_column($rows, 'order_id');
    }
}