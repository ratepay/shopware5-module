<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration100 extends AbstractPluginMigration
{
    public function up($mode)
    {
        $connection = Shopware()->Models()->getConnection();

        if ($connection->getSchemaManager()->tablesExist(['s_plugin_schema_version', 'ratepay_schema_version'])) {

            // if both tables exists, we will migrate the migrations-infos
            $this->addSql("
                REPLACE INTO s_plugin_schema_version SELECT 'RpayRatePay', s.version, s.start_date, s.complete_date, s.name, s.error_msg FROM ratepay_schema_version s;
            ");

            $installedVersions = Shopware()->Models()->getConnection()->fetchAll('SELECT version FROM ratepay_schema_version');
            foreach ($installedVersions as $version) {
                define('RATEPAY_MIGRATION_DONE_' . $version['version'], true);
            }
        }
    }

    public function down($keepUserData)
    {
        $this->addSql("
            DROP TABLE IF EXISTS ratepay_schema_version;
        ");
    }

}




