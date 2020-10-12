<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\Logger;

class PaymentStatusesSetup extends Bootstrapper
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        $sql = 'INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?';
        try {
            Shopware()->Db()->query($sql, [
                155, 'Zahlungsabwicklung durch Ratepay', 155, 'payment', 0
            ]);
        } catch (\Exception $exception) {
            Logger::singleton()->addNotice($exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws \Exception
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
