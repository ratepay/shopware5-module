<?php

namespace RpayRatePay\Bootstrapping\Database;

use RpayRatePay\Component\Service\ShopwareUtil;

class CreateConfigTable
{
    /**
     * @return string
     */
    protected function getQuery()
    {
        $query = 'CREATE TABLE IF NOT EXISTS `rpay_ratepay_config` (' .
            '`profileId` varchar(255) NOT NULL,' .
            '`shopId` int(5) NOT NULL, ' .
            '`invoice` int(2) NOT NULL, ' .
            '`debit` int(2) NOT NULL, ' .
            '`installment` int(2) NOT NULL, ' .
            '`installment0` int(2) NOT NULL, ' .
            '`installmentDebit` int(2) NOT NULL, ' .
            '`device-fingerprint-status` varchar(3) NOT NULL, ' .
            '`device-fingerprint-snippet-id` varchar(55) NULL, ' .
            '`country-code-billing` varchar(30) NULL, ' .
            '`country-code-delivery` varchar(30) NULL, ' .
            '`currency` varchar(30) NULL, ' .
            '`country` varchar(30) NOT NULL, ' .
            "`error-default` VARCHAR(535) NOT NULL DEFAULT 'Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href=\"http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach\" target=\"_blank\">RatePAY-Datenschutzerklärung</a>', " .
            '`sandbox` int(1) NOT NULL, ' .
            'PRIMARY KEY (`shopId`, `country`)' .
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

        $hasColumnBackend = ShopwareUtil::tableHasColumn('rpay_ratepay_config', 'backend');

        if (!$hasColumnBackend) {
            $sql = 'ALTER TABLE rpay_ratepay_config ADD COLUMN backend int(1) NOT NULL';
            $database->query($sql);
        }

        $sql = 'ALTER TABLE rpay_ratepay_config DROP PRIMARY KEY, ADD PRIMARY KEY (shopId, country, backend);';
        $database->query($sql);
    }
}
