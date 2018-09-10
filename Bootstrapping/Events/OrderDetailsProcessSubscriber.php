<?php

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Service\Logger;

class OrderDetailsProcessSubscriber implements \Enlight\Event\SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'insertRatepayPositions',
        ];
    }

    /**
     * Saves Data into the `rpay_ratepay_order_position`
     *
     * @param \Enlight_Event_EventArgs $arguments
     * @return mixed
     * @throws \Zend_Db_Adapter_Exception
     */
    public function insertRatepayPositions(\Enlight_Event_EventArgs $arguments)
    {
        $orderNumber = $arguments->getSubject()->sOrderNumber;
        $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(['number' => $orderNumber]);

        if ($this->isRatePayPayment($orderNumber)) {
            $paymentProcessor = new \RpayRatePay\Component\Service\PaymentProcessor(Shopware()->Db());
            $paymentProcessor->insertRatepayPositions($order);
        }

        return $orderNumber;
    }

    /**
     * @param $orderNumber
     * @return bool
     */
    public function isRatePayPayment($orderNumber)
    {
        $isRatepayPayment = false;
        try {
            $isRatePAYpaymentSQL = 'SELECT COUNT(*) FROM `s_order` '
                . 'JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id`=`s_order`.`paymentID` '
                . "WHERE  `s_order`.`ordernumber`=? AND`s_core_paymentmeans`.`name` LIKE 'rpayratepay%';";
            $isRatepayPayment = (1 <= Shopware()->Db()->fetchOne($isRatePAYpaymentSQL, [$orderNumber]));
        } catch (\Exception $exception) {
            Logger::singleton()->error($exception->getMessage());
        }

        return (bool) $isRatepayPayment;
    }
}
