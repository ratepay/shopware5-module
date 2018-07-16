<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 11:01
 */
namespace RpayRatePay\Bootstrapping\Database;

class CreateConfigPaymentTable
{
    /**
     * @return string
     */
    protected function getQuery()
    {
       $query = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_payment` (" .
           "`rpay_id` int(2) NOT NULL AUTO_INCREMENT," .
           "`status` varchar(255) NOT NULL," .
           "`b2b` int(2) NOT NULL," .
           "`limit_min` int NOT NULL," .
           "`limit_max` int NOT NULL," .
           "`limit_max_b2b` int," .
           "`address` int(2) NOT NULL," .
           "PRIMARY KEY (`rpay_id`)" .
           ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
       return $query;
    }



    /**
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @throws Zend_Db_Adapter_Exception
     */
    public function __invoke($database)
    {
        $database->query("DROP TABLE IF EXISTS `rpay_ratepay_config_payment`");
        $database->query($this->getQuery());
    }
}