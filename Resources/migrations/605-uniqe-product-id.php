<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration605 extends AbstractPluginMigration
{
    // @phpstan-ignore-next-line
    public function up($mode)
    {
        if(defined('RATEPAY_MIGRATION_DONE_605')) {
            return;
        }

        $this->addSql("ALTER TABLE `rpay_ratepay_order_positions` ADD `unique_number` VARCHAR(255) NULL DEFAULT NULL AFTER `s_order_details_id`;");
        $this->addSql("ALTER TABLE `rpay_ratepay_order_discount` ADD `unique_number` VARCHAR(255) NULL DEFAULT NULL AFTER `s_order_details_id`;");
    }

    // @phpstan-ignore-next-line
    public function down($keepUserData)
    {
        $this->addSql("ALTER TABLE `rpay_ratepay_order_positions` DROP `unique_number`;");
        $this->addSql("ALTER TABLE `rpay_ratepay_order_discount` DROP `unique_number`;");
    }

}
