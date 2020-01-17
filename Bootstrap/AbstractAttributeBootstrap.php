<?php


namespace RpayRatePay\Bootstrap;


use Shopware\Bundle\AttributeBundle\Service\CrudService;

abstract class AbstractAttributeBootstrap extends AbstractBootstrap
{

    /**
     * @var CrudService
     */
    protected $crudService;

    /**
     * @return array
     */
    protected abstract function getTables();

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

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->crudService = $this->container->get('shopware_attribute.crud_service');
    }

    private function cleanUp()
    {
        $metaDataCache = $this->modelManager->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels($this->getTables());
    }

    protected abstract function installAttributes();
    protected abstract function uninstallAttributes();

    public function activate()
    {
        // do nothing
    }

    public function deactivate()
    {
        // do nothing
    }
}
