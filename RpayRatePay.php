<?php


/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * Bootstrap
 *
 * @category   RatePAY
 * @package    RpayRatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */

namespace RpayRatePay;

use RpayRatePay\Bootstrap\AbstractBootstrap;
use RpayRatePay\Bootstrap\Configuration;
use RpayRatePay\Bootstrap\Database;
use RpayRatePay\Bootstrap\OrderAttribute;
use RpayRatePay\Bootstrap\OrderStatus;
use RpayRatePay\Bootstrap\PaymentMeans;
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
            new OrderAttribute(),
            new PaymentMeans(),
            new OrderStatus(),
            new Configuration()
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
        $context->scheduleClearCache($context::CACHE_LIST_ALL);
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
