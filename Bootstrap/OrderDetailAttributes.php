<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;


class OrderDetailAttributes extends AbstractAttributeBootstrap
{

    /**
     * @return array
     */
    protected function getTables()
    {
        return [
            's_order_details_attributes'
        ];
    }

    protected function installAttributes()
    {
        $this->crudService->update('s_order_details_attributes', 'ratepay_last_status', 'integer', [
            'displayInBackend' => false
        ], null, null, 0);
    }

    protected function uninstallAttributes()
    {
        if ($this->crudService->get('s_order_details_attributes', 'ratepay_last_status')) {
            $this->crudService->delete('s_order_details_attributes', 'ratepay_last_status');
        }
    }
}
