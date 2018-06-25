<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 11:01
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Database_CreateConfigInstallmentTable
{
    protected $query = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_installment` (" .
        "`rpay_id` int(2) NOT NULL," .
        "`month-allowed` varchar(255) NOT NULL," .
        "`payment-firstday` varchar(10) NOT NULL," .
        "`interestrate-default` float NOT NULL," .
        "`rate-min-normal` float NOT NULL," .
        "PRIMARY KEY (`rpay_id`)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

    /**
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @throws Zend_Db_Adapter_Exception
     */
    public function __invoke($database)
    {
        $database->query("DROP TABLE IF EXISTS `rpay_ratepay_config_installment`");
        $database->query($this->query);
    }
}