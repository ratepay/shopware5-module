<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration601 extends AbstractPluginMigration
{
    /**
     * {@inheritdoc}
     */
    public function up($mode)
    {
        if (self::MODUS_UPDATE === $mode) {
            $connection = Shopware()->Models()->getConnection();
            $schemaManager = $connection->getSchemaManager();
            $tables = [
                'rpay_ratepay_order_discount',
                'rpay_ratepay_order_positions',
                'rpay_ratepay_order_shipping'
            ];
            foreach ($tables as $table) {
                if ($schemaManager->tablesExist([$table])) {
                    $columnList = $schemaManager->listTableColumns($table);
                    if (array_key_exists('tax_rate', $columnList)) {
                        $this->addSql('ALTER TABLE ' . $table . ' DROP `tax_rate`');
                    }
                }
            }
        }
    }

    public function down($keepUserData)
    {
    }

}
