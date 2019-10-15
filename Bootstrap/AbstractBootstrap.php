<?php


namespace RpayRatePay\Bootstrap;


use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

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

    public function __construct(InstallContext $context)
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

    public abstract function install();

    public abstract function update();

    public abstract function uninstall($keepUserData = false);

    public abstract function activate();

    public abstract function deactivate();

}
