<?php

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\Logger;

class CronjobSetup extends Bootstrapper
{
    const UPDATE_TRANSACTIONS_INTERVAL_SECONDS = 3600;
    const UPDATE_TRANSACTIONS_ACTION = 'UpdateRatepayTransactions';
    const LEGACY_TRANSACTIONS_ACTION = 'Shopware_CronJob_UpdateRatepayTransactions';

    /**
     * @throws Exception
     */
    public function install()
    {
        $settings = $this->getCronSettings();
        $this->cleanUpLegacyCronjob();

        if (!$this->hasStoredCronjobs()) {
            $this->bootstrap->createCronJob(
                'RatePAY Transaction Updater',
                self::UPDATE_TRANSACTIONS_ACTION,
                $settings['interval']
            );
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
     * @return array
     */
    public function getCronSettings()
    {
        $row = Shopware()->Db()->fetchRow(
            'SELECT `interval` FROM s_crontab WHERE `action` = ? ORDER BY id',
            [self::LEGACY_TRANSACTIONS_ACTION]
        );

        return !empty($row) ? $row : [
            'interval' => self::UPDATE_TRANSACTIONS_INTERVAL_SECONDS,
        ];
    }

    public function hasStoredCronjobs()
    {
        $id = Shopware()->Db()->fetchOne(
            'SELECT `id` FROM s_crontab WHERE `action` = ? ORDER BY id',
            [self::UPDATE_TRANSACTIONS_ACTION]
        );

        return !empty($id);
    }

    /**
     * Remove all legacy cronjobs from table.
     * Workaround to fix duplicated entries.
     */
    public function cleanUpLegacyCronjob()
    {
        Shopware()->Db()->executeQuery(
            'DELETE FROM s_crontab WHERE `action` = ?',
            [self::LEGACY_TRANSACTIONS_ACTION]
        );
    }

    /**
     * @return mixed|void
     * @throws \Zend_Db_Adapter_Exception
     */
    public function uninstall()
    {
        Shopware()->Db()->query('DELETE FROM s_crontab WHERE `action` = ?', [self::UPDATE_TRANSACTIONS_ACTION]);
    }
}
