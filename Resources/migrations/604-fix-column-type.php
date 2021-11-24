<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration604 extends AbstractPluginMigration
{
    // @phpstan-ignore-next-line
    public function up($mode)
    {
        if (defined('RATEPAY_MIGRATION_DONE_604')) {
            return;
        }

        $this->addSql("ALTER TABLE `ratepay_profile_config_method_installment` CHANGE `rate_min_normal` `rate_min_normal` FLOAT NOT NULL");

    }

    // @phpstan-ignore-next-line
    public function down($keepUserData)
    {
    }

}
