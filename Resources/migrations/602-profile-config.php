<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration602 extends AbstractPluginMigration
{
    /**
     * {@inheritdoc}
     */
    public function up($mode)
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `ratepay_profile_config` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `backend` tinyint(1) NOT NULL,
                `shop_id` int(11) UNSIGNED NOT NULL,
                `profile_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `security_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT '0',
                `country_code_delivery` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
                `country_code_billing` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
                `currency` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
                `error_default` tinytext COLLATE utf8_unicode_ci DEFAULT NULL,
                `sandbox` tinyint(1) NOT NULL,
                PRIMARY KEY (`id`),   
                FOREIGN KEY (`shop_id`) REFERENCES `s_core_shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE( `backend`, `shop_id`, `profile_id`, `sandbox`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS `ratepay_profile_config_method` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `profile_id` int(11) UNSIGNED NOT NULL,
                `payment_method_id` int(11) NOT NULL,
                `allow_b2b` tinyint(1) DEFAULT '0',
                `limit_min` int(11) DEFAULT NULL,
                `limit_max` int(11) DEFAULT NULL,
                `limit_max_b2b` int(11) DEFAULT NULL,
                `allow_different_addresses` tinyint(1) NOT NULL,
                PRIMARY KEY (`id`),   
                FOREIGN KEY (`profile_id`) REFERENCES `ratepay_profile_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`payment_method_id`) REFERENCES `s_core_paymentmeans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE( `profile_id`, `payment_method_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS `ratepay_profile_config_method_installment` (
                `id` int(11) UNSIGNED NOT NULL,
                `month_allowed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `is_banktransfer_allowed` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '0',
                `is_debit_allowed` tinyint(1) DEFAULT '0',
                `rate_min_normal` tinyint(1) NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`id`) REFERENCES `ratepay_profile_config_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Tables got dropped during bootstrapping
//        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config");
//        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config_installment");
//        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config_payment");
    }

    public function down($keepUserData)
    {
        $this->addSql("DROP TABLE IF EXISTS ratepay_profile_config_method_installment");
        $this->addSql("DROP TABLE IF EXISTS ratepay_profile_config_method");
        $this->addSql("DROP TABLE IF EXISTS ratepay_profile_config");
        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config_installment");
        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config_payment");
        $this->addSql("DROP TABLE IF EXISTS rpay_ratepay_config");
    }

}
