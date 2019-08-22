<?php

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Bootstrapping\Database\InstallModels;
use RpayRatePay\Component\Service\ShopwareUtil;

class DatabaseSetup extends Bootstrapper
{
    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function install()
    {
        $setup = new InstallModels(Shopware()->Container()->get('models'));
        $setup->install();
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $setup = new InstallModels(Shopware()->Container()->get('models'));
        $setup->update();

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
        $setup = new InstallModels(Shopware()->Container()->get('models'));
        $setup->uninstall();
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
