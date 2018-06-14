<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 11:22
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailsProcessSubscriber implements \Enlight\Event\SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'insertRatepayPositions',
        ];
    }

    /**
     * Saves Data into the rpay_ratepay_order_position
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function insertRatepayPositions(Enlight_Event_EventArgs $arguments)
    {
        $ordernumber = $arguments->getSubject()->sOrderNumber;

        try {
            $isRatePAYpaymentSQL = "SELECT COUNT(*) FROM `s_order` "
                . "JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id`=`s_order`.`paymentID` "
                . "WHERE  `s_order`.`ordernumber`=? AND`s_core_paymentmeans`.`name` LIKE 'rpayratepay%';";
            $isRatePAYpayment = Shopware()->Db()->fetchOne($isRatePAYpaymentSQL, array($ordernumber));
        } catch (Exception $exception) {
            Shopware()->Pluginlogger()->error($exception->getMessage());
            $isRatePAYpayment = 0;
        }

        if ($isRatePAYpayment != 0) {
            $sql = "SELECT `id` FROM `s_order_details` WHERE `ordernumber`=?;";
            $rows = Shopware()->Db()->fetchAll($sql, array($ordernumber));
            $values = "";
            foreach ($rows as $row) {
                $values .= "(" . $row['id'] . "),";
            }
            $values = substr($values, 0, -1);
            $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES " . $values;
            try {
                Shopware()->Db()->query($sqlInsert);
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }
        }

        return $ordernumber;
    }
}