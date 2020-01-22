<?php


namespace RpayRatePay\Bootstrapping;


use RpayRatePay\Bootstrapping\Bootstrapper;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;

abstract class AbstractAttributeBootstrap extends Bootstrapper
{

    /**
     * @var CrudService
     */
    protected $crudService;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @return array
     */
    protected abstract function getTables();

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->crudService = $bootstrap->get('shopware_attribute.crud_service');
        $this->modelManager = $bootstrap->get('models');
    }

    public final function install()
    {
        $this->installAttributes();
        $this->cleanUp();
    }

    public function update()
    {
        $this->installAttributes();
        $this->cleanUp();
    }

    public final function uninstall($keepUserData = false)
    {
        if($keepUserData === false) {
            $this->uninstallAttributes();
            $this->cleanUp();
        }
    }
    private function cleanUp()
    {
        $metaDataCache = $this->modelManager->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels($this->getTables());
    }

    protected abstract function installAttributes();
    protected abstract function uninstallAttributes();

}
