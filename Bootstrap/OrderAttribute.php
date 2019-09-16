<?php

namespace RpayRatePay\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\Context\InstallContext;

class OrderAttribute extends AbstractBootstrap
{

    /**
     * @var CrudService
     */
    protected $crudService;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        InstallContext $context,
        ModelManager $modelManager,
        CrudService $crudService
    )
    {
        parent::__construct($context);
        $this->modelManager = $modelManager;
        $this->crudService = $crudService;
    }

    public function install()
    {
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_shipping', 'boolean');
        $this->crudService->update('s_order_attributes', 'ratepay_fallback_discount', 'boolean');
        $this->crudService->update('s_order_attributes', 'ratepay_backend', 'boolean');

        $this->cleanUp();
    }

    public function update()
    {
        $this->install();
    }

    public function uninstall($keepUserData = false)
    {
        if($keepUserData === false) {
            $this->crudService->delete('s_order_attributes', 'ratepay_fallback_shipping');
            $this->crudService->delete('s_order_attributes', 'ratepay_fallback_discount');
            $this->crudService->delete('s_order_attributes', 'ratepay_backend');

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


    protected function cleanUp() {
        $this->modelManager->generateAttributeModels([
            's_order_attributes',
        ]);
    }
}
