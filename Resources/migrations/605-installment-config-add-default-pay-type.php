<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
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
        if (defined('RATEPAY_MIGRATION_DONE_605')) {
            return;
        }

        $this->addSql("ALTER TABLE `ratepay_profile_config_method_installment` ADD `default_payment_type` VARCHAR(30) NOT NULL AFTER `is_debit_allowed`;");
    }

    // @phpstan-ignore-next-line
    public function down($keepUserData)
    {
    }

}
