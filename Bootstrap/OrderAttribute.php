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
        $this->crudService->update('s_order_attributes', 'ratepay_profile_id', 'text', [], null, null, null);
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

        if ($this->updateContext &&
            version_compare($this->updateContext->getCurrentVersion(), "6.0.0", "<")
        ) {
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

        if ($this->updateContext &&
            version_compare($this->updateContext->getCurrentVersion(), "6.0.0", "<")
        ) {
            $backendDeviceType = $this->container->get('config')
                ->getByNamespace('SwagBackendOrder', 'desktopTypes') ?: 'Backend';

            if($this->modelManager->getConnection()->getSchemaManager()->tablesExist(['rpay_ratepay_config'])) {
                $connection->exec("
                    UPDATE s_order_attributes attr
                        INNER JOIN s_order o ON (attr.orderID = o.id)
                        INNER JOIN s_order_attributes oa ON (o.id = oa.orderID)
                        INNER JOIN s_core_paymentmeans p ON (p.id = o.paymentID)
                        INNER JOIN s_order_billingaddress a ON (o.id = a.orderID)
                        INNER JOIN s_core_countries co ON (co.id = a.countryID)
                        INNER JOIN rpay_ratepay_config config ON (
                            config.shopId = o.subshopID AND 
                            config.country_code_billing LIKE CONCAT('%',co.countryiso,'%') AND 
                            (
                                (p.name != '" . PaymentMethods::PAYMENT_INSTALLMENT0 . "' AND config.is_zero_percent_installment = 0) OR
                                (p.name = '" . PaymentMethods::PAYMENT_INSTALLMENT0 . "' AND config.is_zero_percent_installment = 1)
                            ) 
                            AND 
                            (
                                (o.deviceType = '" . $backendDeviceType . "' AND config.backend = 1) OR					
                                (o.deviceType != '" . $backendDeviceType . "' AND config.backend = 0)
                            )
                        )
                    SET attr.ratepay_profile_id = config.profileId    
                    WHERE 
                        p.name LIKE 'rpay%' AND attr.ratepay_profile_id IS NULL
                ");
            }
        }
    }

    protected function uninstallAttributes()
    {
        $this->crudService->delete('s_order_attributes', 'ratepay_profile_id');
        $this->crudService->delete('s_order_attributes', 'ratepay_descriptor');
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_shipping');
        $this->crudService->delete('s_order_attributes', 'ratepay_fallback_discount');
        $this->crudService->delete('s_order_attributes', 'ratepay_backend');
        $this->crudService->delete('s_order_attributes', 'ratepay_direct_delivery');
    }
}
