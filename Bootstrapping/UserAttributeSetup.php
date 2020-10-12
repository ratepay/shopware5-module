<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping;


class UserAttributeSetup extends AbstractAttributeBootstrap
{

    protected function getTables()
    {
        return [
            's_user_attributes'
        ];
    }

    protected function installAttributes()
    {
        $this->crudService->update('s_user_attributes', 'ratepay_locked_payment_methods', 'text', [
            'displayInBackend' => false
        ], null, null, false);
    }

    protected function uninstallAttributes()
    {
        $this->crudService->delete('s_user_attributes', 'ratepay_locked_payment_methods');
    }
}
