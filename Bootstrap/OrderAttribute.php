<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;

use RpayRatePay\Enum\PaymentMethods;

class OrderAttribute extends AbstractAttributeBootstrap
{

    /**
     * @return array
     */
    protected function getTables()
    {
        return [
            's_order_attributes',
        ];
    }

    protected function installAttributes()
    {
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean', [], null, null, false);
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean', [], null, null, false);
        $this->crudService->update('s_order_attributes', 'ratepay_backend', 'boolean');
        $addDirectDeliveryAttribute = $this->crudService->get('s_order_attributes', 'ratepay_direct_delivery') == null;
        $this->crudService->update('s_order_attributes', 'ratepay_direct_delivery', 'boolean', [], null, null, 1);

        if ($addDirectDeliveryAttribute) {
            $this->modelManager->getConnection()->exec("
                UPDATE s_order_attributes attr
                    INNER JOIN s_order s_order ON (s_order.id = attr.id)
                    INNER JOIN s_core_paymentmeans payment ON (s_order.paymentID = payment.id)
                    set attr.ratepay_direct_delivery = 0 
                    WHERE payment.name IN ('" . PaymentMethods::PAYMENT_RATE . "', '" . PaymentMethods::PAYMENT_INSTALLMENT0 . "')
            ");
        }
    }

    protected function uninstallAttributes()
    {
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_shipping');
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_discount');
        $this->crudService->delete('s_order_attributes', 'ratepay_backend');
        $this->crudService->delete('s_order_attributes', 'ratepay_direct_delivery');
    }
}
