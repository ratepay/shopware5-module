<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 10.07.18
 * Time: 11:26
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_AdditionalOrderAttributeSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    public function install()
    {
        $service = $this->bootstrap->get('shopware_attribute.crud_service');
        $service->update(
            's_order_attributes',
            'ratepay_fallback_shipping',
            'boolean'
        );
    }

    public function update()
    {
        if ($this->fallbackShippingColumnExists()) {
            return;
        }

        $this->install();
    }

    public function uninstall()
    {
        // $service = $this->bootstrap->get('shopware_attribute.crud_service');
        // $service->delete('s_articles_attributes', 'my_column');
    }

    private function fallbackShippingColumnExists()
    {
        $query = '
            SELECT * 
            FROM information_schema.COLUMNS 
            WHERE TABLE_NAME = \'s_order_attributes\' 
            AND COLUMN_NAME = \'ratepay_fallback_shipping\'
        ';

        $result = Shopware()->Db()->query($query)->fetchAll();
        return !empty($result);
    }
}