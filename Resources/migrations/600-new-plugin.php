<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration600 extends AbstractPluginMigration
{
    public function up($mode)
    {
        if (self::MODUS_UPDATE === $mode) {

            $this->addSql("
                ALTER TABLE rpay_ratepay_order_discount
                    CHANGE s_order_detail_id s_order_details_id INT NOT NULL,
                    DROP PRIMARY KEY;
                ;
            ");

            $this->addSql("
                ALTER TABLE rpay_ratepay_order_discount
                    DROP s_order_id,
                    ADD PRIMARY KEY (s_order_details_id),
                    ADD FOREIGN KEY (`s_order_details_id`) REFERENCES `s_order_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ");

            $this->addSql("    
                ALTER TABLE rpay_ratepay_order_history
                    CHANGE event event VARCHAR(100) DEFAULT NULL,
                    CHANGE articlename articlename VARCHAR(100) DEFAULT NULL,
                    CHANGE articlenumber articlenumber VARCHAR(50) DEFAULT NULL,
                    CHANGE quantity quantity VARCHAR(50) DEFAULT NULL;
            ");

            $this->addSql("
                ALTER TABLE rpay_ratepay_logging
                    CHANGE version version VARCHAR(10) DEFAULT 'N/A' NOT NULL,
                    CHANGE operation operation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                    CHANGE suboperation suboperation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                    CHANGE transactionid transactionid VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                    CHANGE firstname firstname VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                    CHANGE lastname lastname VARCHAR(255) DEFAULT 'N/A' NOT NULL
            ");

        } else if ($mode == self::MODUS_INSTALL) {
            $this->addSql("
            
            CREATE TABLE IF NOT EXISTS `rpay_ratepay_logging` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `date` datetime NOT NULL,
              `version` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `operation` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `suboperation` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `transactionId` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N/A',
              `request` longtext COLLATE utf8_unicode_ci NOT NULL,
              `response` longtext COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_discount` (
              `s_order_details_id` int(11) NOT NULL,
              `delivered` int(11) NOT NULL DEFAULT 0,
              `cancelled` int(11) NOT NULL DEFAULT 0,
              `returned` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`s_order_details_id`),
              CONSTRAINT `rpay_ratepay_order_discount_ibfk_1` FOREIGN KEY (`s_order_details_id`) REFERENCES `s_order_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_history` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `orderId` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
              `date` datetime NOT NULL,
              `event` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
              `articlename` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
              `articlenumber` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
              `quantity` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_positions` (
              `s_order_details_id` int(11) NOT NULL,
              `delivered` int(11) NOT NULL DEFAULT 0,
              `cancelled` int(11) NOT NULL DEFAULT 0,
              `returned` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`s_order_details_id`),
              CONSTRAINT `rpay_ratepay_order_positions_ibfk_1` FOREIGN KEY (`s_order_details_id`) REFERENCES `s_order_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_shipping` (
              `s_order_id` int(11) NOT NULL,
              `delivered` int(11) NOT NULL DEFAULT 0,
              `cancelled` int(11) NOT NULL DEFAULT 0,
              `returned` int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY (`s_order_id`),
              CONSTRAINT `rpay_ratepay_order_shipping_ibfk_1` FOREIGN KEY (`s_order_id`) REFERENCES `s_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
   
        ");
        }
    }

    public function down($keepUserData)
    {
    }

}
