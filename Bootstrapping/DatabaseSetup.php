<?php

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\ShopwareUtil;

class DatabaseSetup extends Bootstrapper
{
    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function install()
    {
        $this->createDatabaseTables();
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $this->updateConfigurationTables();
        $this->removeSandboxColumns();
    }

    /**
     * Drops all RatePAY database tables
     *
     * @return mixed|void
     * @throws \Exception
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
             * Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_discount`");
             */

            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_history`");
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_installment`");
            Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_payment`");
        } catch (\Exception $exception) {
            throw new \Exception('Can not delete RatePAY tables - ' . $exception->getMessage());
        }
    }


    /**
     * @throws \Exception
     */
    protected function createDatabaseTables()
    {
        $tables = [
            new \RpayRatePay\Bootstrapping\Database\CreateLoggingTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateConfigTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderPositionsTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderShippingTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderDiscountTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderHistoryTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateConfigPaymentTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateConfigInstallmentTable(),
        ];

        try {
            foreach ($tables as $generator) {
                $generator(Shopware()->Db());
            }
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new \Exception('Can not create Database.' . $exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function updateConfigurationTables()
    {
        $tables = [
            new \RpayRatePay\Bootstrapping\Database\CreateConfigTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateConfigPaymentTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateConfigInstallmentTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderPositionsTable(),
            new \RpayRatePay\Bootstrapping\Database\CreateOrderShippingTable(),
        ];

        try {
            foreach ($tables as $generator) {
                $generator(Shopware()->Db());
            }
        } catch (\Exception $exception) {
            throw new \Exception('Can not update Database.' . $exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function removeSandboxColumns()
    {
        if (ShopwareUtil::tableHasColumn('s_core_config_elements', 'RatePaySandboxDE')) {
            try {
                Shopware()->Db()->query(
                    "DELETE FROM `s_core_config_elements` WHERE `s_core_config_elements`.`name` LIKE 'RatePaySandbox%'"
                );
            } catch (\Exception $exception) {
                throw new \Exception("Can't remove Sandbox fields` - " . $exception->getMessage());
            }
        }
    }
}
