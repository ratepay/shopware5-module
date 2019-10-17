<?php

namespace RpayRatePay\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;

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
        $this->crudService->update('s_order_attributes', 'ratepay_direct_delivery', 'boolean', [], null, null, false);

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
        // TODO: Implement activate() method.
    }

    public function deactivate()
    {
        // TODO: Implement deactivate() method.
    }
}
