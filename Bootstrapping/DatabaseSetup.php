<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:26
 */
namespace Shopware\RatePAY\Bootstrapping;

use Shopware\RatePAY\Bootstrapping\Bootstrapper;

class DatabaseSetup extends Bootstrapper
{
    /**
     * @return mixed|void
     * @throws Exception
     */
    public function install() {
        $this->createDatabaseTables();
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $this->updateConfigurationTables();
        $this->removeSandboxColumns();
    }

    /**
     * Drops all RatePAY database tables
     *
     * @return mixed|void
     * @throws Exception
     */
    public function uninstall()
    {
        try {
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_logging`");
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config`");

            /*
             * These tables are not deleted. This makes possible to manage the
             * orders after a new Plugin installation
             *
             * Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_positions`");
             * Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_shipping`");
             */

            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_history`");
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_installment`");
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_payment`");
        } catch (\Exception $exception) {
            throw new Exception('Can not delete RatePAY tables - ' . $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    protected function createDatabaseTables()
    {
        $tables = [
            new \Shopware\RatePAY\Bootstrapping\Database\CreateLoggingTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateOrderPositionsTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateOrderShippingTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateOrderHistoryTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigPaymentTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigInstallmentTable(),
        ];

        try {
            foreach ($tables as $generator) {
                $generator(Shopware()->Db());
            }
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception('Can not create Database.' . $exception->getMessage());
        }
    }

    private function updateConfigurationTables()
    {
        $tables = [
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigPaymentTable(),
            new \Shopware\RatePAY\Bootstrapping\Database\CreateConfigInstallmentTable(),
        ];

        try {
            foreach ($tables as $generator) {
                $generator(Shopware()->Db());
            }
        } catch (\Exception $exception) {
            throw new Exception('Can not update Database.' . $exception->getMessage());
        }
    }

    private function removeSandboxColumns()
    {
        if ($this->checkIfColumnExists('s_core_config_elements', 'RatePaySandboxDE')) {
            try {
                Shopware()->Db()->query(
                    "DELETE FROM `s_core_config_elements` WHERE `s_core_config_elements`.`name` LIKE 'RatePaySandbox%'"
                );
            } catch (\Exception $exception) {
                throw new Exception("Can't remove Sandbox fields` - " . $exception->getMessage());
            }
        }
    }

    /**
     * Checks if column exists
     *
     * @param string $table
     * @param string $column
     * @throws Exception
     * @return bool
     */
    private function checkIfColumnExists($table, $column)
    {
        try {
            $columnExists = Shopware()->Db()->fetchRow(
                "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'"
            );
        } catch (\Exception $exception) {
            throw new Exception("Can not enter table " . $table . " - " . $exception->getMessage());
        }

        return (bool) $columnExists;
    }
}