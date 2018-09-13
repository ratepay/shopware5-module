<?php

namespace RpayRatePay\Bootstrapping\Database;

class CreateOrderHistoryTable
{
    /**
     * @return string
     */
    private function getQuery()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_history` (' .
            '`id` int(11) NOT NULL AUTO_INCREMENT,' .
            '`orderId` varchar(50) ,' .
            '`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ' .
            '`event` varchar(100), ' .
            '`articlename` varchar(100), ' .
            '`articlenumber` varchar(50), ' .
            '`quantity` varchar(50), ' .
            'PRIMARY KEY (`id`)' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

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
