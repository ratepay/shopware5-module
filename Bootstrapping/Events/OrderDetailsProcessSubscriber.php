<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 11:22
 */
namespace Shopware\RatePAY\Bootstrapping\Events;

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

        if ($this->isRatePayPayment($orderNumber)) {
            $sql = "SELECT `id` FROM `s_order_details` WHERE `ordernumber`=?;";
            $rows = Shopware()->Db()->fetchAll($sql, array($orderNumber));
            $values = "";
            foreach ($rows as $row) {
                $values .= "(" . $row['id'] . "),";
            }
            $values = substr($values, 0, -1);
            $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES " . $values;
            try {
                Shopware()->Db()->query($sqlInsert);
            } catch (\Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }
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
            $isRatePAYpaymentSQL = "SELECT COUNT(*) FROM `s_order` "
                . "JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id`=`s_order`.`paymentID` "
                . "WHERE  `s_order`.`ordernumber`=? AND`s_core_paymentmeans`.`name` LIKE 'rpayratepay%';";
            $isRatepayPayment = (1 <= Shopware()->Db()->fetchOne($isRatePAYpaymentSQL, array($orderNumber)));
        } catch (\Exception $exception) {
            Shopware()->Pluginlogger()->error($exception->getMessage());
        }

        return (bool) $isRatepayPayment;
    }
}