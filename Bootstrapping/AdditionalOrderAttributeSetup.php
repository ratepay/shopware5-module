<?php

namespace RpayRatePay\Bootstrapping;

class AdditionalOrderAttributeSetup extends AbstractAttributeBootstrap
{
    /**
     * @return array
     */
    protected function getTables()
    {
        return [
            's_order_attributes'
        ];
    }

    protected function installAttributes()
    {
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean');
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean');
        $this->crudService->update('s_order_attributes', 'ratepay_backend', 'boolean');
    }

    protected function uninstallAttributes()
    {
        // $service->delete('s_articles_attributes', 'my_column');
    }
}
