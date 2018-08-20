<?php
namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Bootstrapping\Bootstrapper;

class CronjobSetup extends Bootstrapper
{
    const UPDATE_TRANSACTIONS_INTERVAL_SECONDS = 3600;
    const UPDATE_TRANSACTIONS_ACTION = 'UpdateRatepayTransactions';

    /**
     * @throws Exception
     */
    public function install()
    {
        $id = Shopware()->Db()->fetchOne('SELECT id from s_crontab WHERE `action` = ?', [self::UPDATE_TRANSACTIONS_ACTION]);
        if ($id === false) {
            $this->bootstrap->createCronJob('RatePAY Transaction Updater', self::UPDATE_TRANSACTIONS_ACTION, self::UPDATE_TRANSACTIONS_INTERVAL_SECONDS);
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update()
    {
        $this->install();
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
        Shopware()->Db()->query('DELETE FROM s_crontab WHERE `action` = ?', [self::UPDATE_TRANSACTIONS_ACTION]);
    }
}