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
        $paymentMethods = Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderOperationsSubscriber::PAYMENT_METHODS;
        $orderStatus = [
            $config['RatePayFullDelivery'],
            $config['RatePayFullCancellation'],
            $config['RatePayFullReturn'],
        ];

        $changeDate = $this->getLastUpdateDate();

        if (empty($changeDate)) {
            $date = new DateTime();
            $date->sub(new DateInterval('P1D'));
            $changeDate = $date->format('Y-m-d H:i:s');
        }

        $query = 'SELECT id FROM s_order o
                LEFT JOIN s_order_details od ON od.orderID = o.id
                LEFT JOIN s_order_history oh ON oh.orderID = o.id
                LEFT JOIN s_corepayment_means cp ON cp.id = o.paymentID
                WHERE cp.name in ('. join(',', $paymentMethods) . ')
                AND o.status in ('. join(',', $orderStatus) .') 
                AND oh.change_date >= :changeDate';

        $rows = Shopware()->Db()->fetchAll($query, [':changeDate', $changeDate]);
        return array_column($rows, 'id');
    }
}