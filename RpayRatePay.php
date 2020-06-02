<?php


/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay;

use RpayRatePay\Bootstrap\AbstractBootstrap;
use RpayRatePay\Bootstrap\Configuration;
use RpayRatePay\Bootstrap\Database;
use RpayRatePay\Bootstrap\OrderAttribute;
use RpayRatePay\Bootstrap\OrderStatus;
use RpayRatePay\Bootstrap\PaymentMeans;
use RpayRatePay\Bootstrap\ProfileConfig;
use RpayRatePay\Bootstrap\UserAttribute;
use RpayRatePay\Services\Logger\FileLogger;
use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RpayRatePay extends Plugin
{

    public static function isPackage()
    {
        return file_exists(self::getPackageVendorAutoload());
    }

    public static function getPackageVendorAutoload()
    {
        return __DIR__ . '/vendor/autoload.php';
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $loggerServiceName = $this->getContainerPrefix() . '.logger';
        if ($container->has($loggerServiceName) === false) {
            // SW 5.6 auto register a logger for each plugin - so if service not found
            // (cause lower sw-version than 5.6), we will register our own logger
            $container->register($loggerServiceName, FileLogger::class)
                ->addArgument($container->getParameter('kernel.logs_dir'));
        }
    }

    /**
     * @param Plugin\Context\InstallContext $context
     * @return AbstractBootstrap[]
     */
    protected function getBootstrapClasses(Plugin\Context\InstallContext $context)
    {
        /** @var AbstractBootstrap[] $bootstrapper */
        $bootstrapper = [
            new Database(),
            new ProfileConfig(),
            new OrderAttribute(),
            new PaymentMeans(),
            new OrderStatus(),
            new Configuration(),
            new UserAttribute()
        ];

        $logger = new FileLogger($this->container->getParameter('kernel.logs_dir'));
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->setContext($context);
            $bootstrap->setLogger($logger);
            $bootstrap->setContainer($this->container);
            $bootstrap->setPluginDir($this->getPath());
        }
        return $bootstrapper;
    }

    public function install(Plugin\Context\InstallContext $context)
    {
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->preInstall();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->install();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->postInstall();
        }
        parent::install($context);
        //$context->scheduleClearCache($context::CACHE_LIST_ALL); // RATEPLUG-70: prevent cache-popups
    }

    public function update(Plugin\Context\UpdateContext $context)
    {
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->preUpdate();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->update();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->postUpdate();
        }
        parent::update($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    public function uninstall(Plugin\Context\UninstallContext $context)
    {
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->preUninstall();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->uninstall($context->keepUserData());
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->postUninstall();
        }
        parent::uninstall($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    public function deactivate(Plugin\Context\DeactivateContext $context)
    {
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->preDeactivate();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->deactivate();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->postDeactivate();
        }
        parent::deactivate($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }

    public function activate(Plugin\Context\ActivateContext $context)
    {
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->preActivate();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->activate();
        }
        foreach ($this->getBootstrapClasses($context) as $bootstrap) {
            $bootstrap->postActivate();
        }
        parent::activate($context);
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
    }
}

if (RpayRatePay::isPackage()) {
    require_once RpayRatePay::getPackageVendorAutoload();
}
