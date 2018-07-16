<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 11:01
 */
namespace RpayRatePay\Bootstrapping\Database;

class CreateLoggingTable
{
    /**
     * @return string
     */
    protected function getQuery()
    {
        $query = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_logging` (" .
            "`id` int(11) NOT NULL AUTO_INCREMENT," .
            "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP," .
            "`version` varchar(10) DEFAULT 'N/A'," .
            "`operation` varchar(255) DEFAULT 'N/A'," .
            "`suboperation` varchar(255) DEFAULT 'N/A'," .
            "`transactionId` varchar(255) DEFAULT 'N/A'," .
            "`firstname` varchar(255) DEFAULT 'N/A'," .
            "`lastname` varchar(255) DEFAULT 'N/A'," .
            "`request` text," .
            "`response` text," .
            "PRIMARY KEY (`id`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        return $query;
    }


    /**
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @throws Zend_Db_Adapter_Exception
     */
    public function __invoke($database)
    {
        $database->query($this->getQuery());
    }
}