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
        // Attribute management for Shopware 5.0.x - 5.1.x
        if (!$this->assertMinimumVersion('5.2.0')) {
            $this->bootstrap->Application()->Models()->addAttribute(
                's_order_attributes',
                'ratepay',
                'fallback_shipping',
                'TINYINT(1)',
                true,
                0
            );
        } else {
            $attributeService = $this->bootstrap->get('shopware_attribute.crud_service');
            $attributeService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean');
        }

        $this->bootstrap->get('models')->generateAttributeModels([
            's_order_attributes',
        ]);
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

    /**
     * @param $version
     * @return bool
     */
    private function assertMinimumVersion($version)
    {
        $expected = explode('.', $version);
        $configured = explode('.', Shopware()->Config()->version);

        return ($expected[0] >= $configured[0])
            && ($expected[1] >= $configured[1])
            && ($expected[2] >= $configured[2]);
    }
}