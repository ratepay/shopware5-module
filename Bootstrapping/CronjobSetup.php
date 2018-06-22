<?php

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_CronjobSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    const UPDATE_TRANSACTIONS_CRON_INTERVAL = 60 * 60;


    /**
     * @throws Exception
     */
    public function install()
    {
        $this->bootstrap->createCronJob('RatePAY Transaction Updater', 'UpdateRatepayTransactions', self::UPDATE_TRANSACTIONS_CRON_INTERVAL);
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update()
    {

    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {

    }
}