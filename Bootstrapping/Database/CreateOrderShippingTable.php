<?php

namespace RpayRatePay\Bootstrapping\Database;

use RpayRatePay\Component\Service\ShopwareUtil;

class CreateOrderShippingTable
{
    /**
     * @return string
     */
    protected function getQuery()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_shipping` (' .
            '`s_order_id` int(11) NOT NULL,' .
            '`delivered` int NOT NULL DEFAULT 0, ' .
            '`cancelled` int NOT NULL DEFAULT 0, ' .
            '`returned` int NOT NULL DEFAULT 0, ' .
            '`tax_rate` int NULL DEFAULT -1, ' .
            'PRIMARY KEY (`s_order_id`)' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
        return $query;
    }

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @throws \Zend_Db_Adapter_Exception
     */
    public function __invoke($database)
    {
        $database->query($this->getQuery());

        $hasColumnTaxRate = ShopwareUtil::tableHasColumn('rpay_ratepay_order_shipping', 'tax_rate');
        if (!$hasColumnTaxRate) {
            $sql = 'ALTER TABLE rpay_ratepay_order_shipping ADD COLUMN tax_rate int(2) NULL DEFAULT NULL';
            $database->query($sql);
        }
    }
}
