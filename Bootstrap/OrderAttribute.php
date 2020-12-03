<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
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
        $this->crudService->update('s_order_attributes', 'ratepay_descriptor', 'text', [], null, null, null);
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean', [], null, null, 0);
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean', [], null, null, 0);
        $this->crudService->update('s_order_attributes', 'ratepay_backend', 'boolean');
        $addDirectDeliveryAttribute = $this->crudService->get('s_order_attributes', 'ratepay_direct_delivery') === null;
        $this->crudService->update('s_order_attributes', 'ratepay_direct_delivery', 'boolean', [], null, null, 1);

        $connection = $this->modelManager->getConnection();
        if ($addDirectDeliveryAttribute) {
            $connection->exec("
                UPDATE s_order_attributes attr
                    INNER JOIN s_order s_order ON (s_order.id = attr.id)
                    INNER JOIN s_core_paymentmeans payment ON (s_order.paymentID = payment.id)
                    SET 
                        attr.ratepay_direct_delivery = 0 
                    WHERE 
                        payment.name IN ('" . PaymentMethods::PAYMENT_RATE . "', '" . PaymentMethods::PAYMENT_INSTALLMENT0 . "')
            ");
        }

        if (version_compare($this->getOldVersion(), "6.0.0", "<")) {
            $connection->exec("
                UPDATE s_order_attributes attr
                    INNER JOIN s_order s_order ON (s_order.id = attr.id)
                    INNER JOIN s_core_paymentmeans payment ON (s_order.paymentID = payment.id)
                    SET 
                        attr.ratepay_descriptor = attr.attribute5
                    WHERE 
                        payment.name IN ('" . implode("','", PaymentMethods::getNames()) . "') AND 
                        attr.ratepay_descriptor IS NULL
            ");
            $connection->exec("
                UPDATE s_order_attributes attr
                    INNER JOIN s_order s_order ON (s_order.id = attr.id)
                    INNER JOIN s_core_paymentmeans payment ON (s_order.paymentID = payment.id)
                    SET 
                        attr.attribute5 = NULL, 
                        attr.attribute6 = NULL
                    WHERE 
                        payment.name IN ('" . implode("','", PaymentMethods::getNames()) . "')
            ");
        }
    }

    protected function uninstallAttributes()
    {
        $this->crudService->delete('s_order_attributes', 'ratepay_descriptor');
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_shipping');
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_discount');
        $this->crudService->delete('s_order_attributes', 'ratepay_backend');
        $this->crudService->delete('s_order_attributes', 'ratepay_direct_delivery');
    }
}
