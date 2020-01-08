<?php

namespace RpayRatePay\Bootstrap;

use Doctrine\ORM\Query\Expr\Join;
use RpayRatePay\Enum\PaymentMethods;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Models\Attribute\Order as OrderAttributeModel;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;

class OrderAttribute extends AbstractBootstrap
{

    /**
     * @var CrudService
     */
    protected $crudService;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->crudService = $this->container->get('shopware_attribute.crud_service');
    }

    public function update()
    {
        $this->install();
    }

    public function install()
    {
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean', [], null, null, false);
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean', [], null, null, false);
        $this->crudService->update('s_order_attributes', 'ratepay_backend', 'boolean');
        $addDirectDeliveryAttribute = $this->crudService->get('s_order_attributes', 'ratepay_direct_delivery') == null;
        $this->crudService->update('s_order_attributes', 'ratepay_direct_delivery', 'boolean', [], null, null, 1);

        if($addDirectDeliveryAttribute) {
            $this->modelManager->getConnection()->exec("
                UPDATE s_order_attributes attr
                    INNER JOIN s_order s_order ON (s_order.id = attr.id)
                    INNER JOIN s_core_paymentmeans payment ON (s_order.paymentID = payment.id)
                    set attr.ratepay_direct_delivery = 0 
                    WHERE payment.name IN ('".PaymentMethods::PAYMENT_RATE."', '".PaymentMethods::PAYMENT_INSTALLMENT0."')
            ");
        }


        $this->cleanUp();
    }

    protected function cleanUp()
    {
        $this->modelManager->generateAttributeModels([
            's_order_attributes',
        ]);
    }

    public function uninstall($keepUserData = false)
    {
        if ($keepUserData === false) {
            $this->crudService->delete('s_order_attributes', 'ratepay_fallback_shipping');
            $this->crudService->delete('s_order_attributes', 'ratepay_fallback_discount');
            $this->crudService->delete('s_order_attributes', 'ratepay_backend');
            $this->crudService->delete('s_order_attributes', 'ratepay_direct_delivery');

            $this->cleanUp();
        }
    }

    public function activate()
    {
        // do nothing
    }

    public function deactivate()
    {
        // do nothing
    }
}
