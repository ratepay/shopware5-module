<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */
namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Bootstrapping\Bootstrapper;
use RpayRatePay\Component\Service\Logger;

class DeliveryStatusesSetup extends Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        $sql = "INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?";
        try {
            Shopware()->Db()->query($sql, array(
                255, 'Teil-(Retoure)', 255, 'state', 0
            ));
        } catch (\Exception $exception) {
            Logger::singleton()->addNotice($exception->getMessage());
        }
        try {
            Shopware()->Db()->query($sql, array(
                265, 'Teil-(Storno)', 265, 'state', 0
            ));
        } catch (\Exception $exception) {
            Logger::singleton()->addNotice($exception->getMessage());
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