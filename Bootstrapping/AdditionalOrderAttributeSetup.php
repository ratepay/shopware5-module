<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        
    }
}
