<?php

namespace RpayRatePay\Bootstrapping\Database;

class CreateOrderDiscountTable
{
    /**
     * @return string
     */
    protected function getQuery()
    {
        $query = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_discount` (
          `s_order_id` int(11) NOT NULL,
          `s_order_detail_id` int(11) NOT NULL,
          `delivered` int(11) NOT NULL DEFAULT '0',
          `cancelled` int(11) NOT NULL DEFAULT '0',
          `returned` int(11) NOT NULL DEFAULT '0',
          `tax_rate` int NULL DEFAULT 0,
          UNIQUE KEY `s_order_id` (`s_order_id`,`s_order_detail_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        return $query;
    }

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @throws \Zend_Db_Adapter_Exception
     */
    public function __invoke($database)
    {
        $database->query($this->getQuery());
    }
}
