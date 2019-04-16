<?php

namespace RpayRatePay\Bootstrapping;

class AdditionalOrderAttributeSetup extends Bootstrapper
{
    public function install()
    {
        // Attribute management for Shopware 5.0.x - 5.1.x
        if (!$this->assertMinimumVersion('5.2.2')) {
            $this->bootstrap->Application()->Models()->addAttribute(
                's_order_attributes',
                'ratepay',
                'fallback_shipping',
                'TINYINT(1)',
                true,
                0
            );
            $this->bootstrap->Application()->Models()->addAttribute(
                's_order_attributes',
                'ratepay',
                'fallback_discount',
                'TINYINT(1)',
                true,
                0
            );
            $this->bootstrap->Application()->Models()->addAttribute(
                's_order_attributes',
                'ratepay',
                'backend',
                'TINYINT(1)',
                false,
                0
            );
        } else {
            $attributeService = $this->bootstrap->get('shopware_attribute.crud_service');
            $attributeService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean');
            $attributeService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean');
            $attributeService->update('s_order_attributes', 'ratepay_backend', 'boolean');
        }

        $this->bootstrap->get('models')->generateAttributeModels([
            's_order_attributes',
        ]);
    }

    public function update()
    {
        $this->install();
    }

    public function uninstall()
    {
        // $service = $this->bootstrap->get('shopware_attribute.crud_service');
        // $service->delete('s_articles_attributes', 'my_column');
    }

    /**
     * @param $version
     * @return bool
     */
    private function assertMinimumVersion($version)
    {
        $sExpected = explode('.', $version);
        $expected = array_map('intval', $sExpected);
        $sConfigured = explode('.', Shopware()->Config()->version);
        $configured = array_map('intval', $sConfigured);

        for ($i = 0; $i < 3; $i++) {
            if ($expected[$i] < $configured[$i]) {
                return true;
            }

            if ($expected[$i] > $configured[$i]) {
                return false;
            }
        }

        return true;
    }
}
