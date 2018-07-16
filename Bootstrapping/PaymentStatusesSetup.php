<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */
namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Bootstrapping\Bootstrapper;

class PaymentStatusesSetup extends Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        $sql = "INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?";
        try {
            Shopware()->Db()->query($sql, array(
                155, 'Zahlungsabwicklung durch RatePAY', 155, 'payment', 0
            ));
        } catch (\Exception $exception) {
            Shopware()->Pluginlogger()->addNotice($exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {}

    /**
     * @return mixed|void
     */
    public function uninstall() {}
}