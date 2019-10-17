<?php


namespace RpayRatePay\Bootstrap;


use Monolog\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractBootstrap
{

    /**
     * @var InstallContext|UpdateContext
     */
    protected $updateContext;
    /**
     * @var InstallContext|UninstallContext
     */
    protected $uninstallContext;
    /**
     * @var ActivateContext|InstallContext
     */
    protected $activateContext;
    /**
     * @var DeactivateContext|InstallContext
     */
    protected $deactivateContext;
    /**
     * @var InstallContext
     */
    protected $installContext;

    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var Logger
     */
    protected $logger;

    public final function __construct()
    {
    }

    public abstract function install();

    public abstract function update();

    public abstract function uninstall($keepUserData = false);

    public abstract function activate();

    public abstract function deactivate();

    public function setContainer($container)
    {
        $this->container = $container;
        $this->modelManager = $this->container->get('models');
    }

    public function setContext(InstallContext $context)
    {
        if ($context instanceof UpdateContext) {
            $this->updateContext = $context;
        } else if ($context instanceof UninstallContext) {
            $this->uninstallContext = $context;
        } else if ($context instanceof ActivateContext) {
            $this->activateContext = $context;
        } else if ($context instanceof DeactivateContext) {
            $this->deactivateContext = $context;
        }
        $this->installContext = $context;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

}
