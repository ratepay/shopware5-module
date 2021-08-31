<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration603 extends AbstractPluginMigration
{
    // @phpstan-ignore-next-line
    public function up($mode)
    {
        if(defined('RATEPAY_MIGRATION_DONE_603')) {
            return;
        }

        $this->addSql("
            ALTER TABLE `ratepay_profile_config_method_installment`
                ADD `interest_rate_default` FLOAT NOT NULL DEFAULT '0' AFTER `rate_min_normal`,
                ADD `service_charge` FLOAT NOT NULL DEFAULT '0' AFTER `interest_rate_default`;
        ");

    }

    // @phpstan-ignore-next-line
    public function down($keepUserData)
    {
        $this->addSql("
            ALTER TABLE `ratepay_profile_config_method_installment`
                DROP COLUMN `interest_rate_default`,
                DROP COLUMN `service_charge`;
        ");
    }

}
