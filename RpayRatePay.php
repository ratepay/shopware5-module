<?php


/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use RpayRatePay\Bootstrap\AbstractBootstrap;
use RpayRatePay\Bootstrap\Configuration;
use RpayRatePay\Bootstrap\Database;
use RpayRatePay\Bootstrap\OrderAttribute;
use RpayRatePay\Bootstrap\OrderDetailAttributes;
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
        if ($container->hasParameter('rpay_rate_pay.logger.max_files') === false) {
            $container->setParameter('rpay_rate_pay.logger.max_files', 5);
            $container->setParameter('rpay_rate_pay.logger.level', Logger::DEBUG);
        }

        parent::build($container);
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
            new OrderDetailAttributes(),
            new PaymentMeans(),
            new OrderStatus(),
            new Configuration(),
            new UserAttribute()
        ];

        if($this->container->has('ratepay.logger')) {
            $logger = $this->container->get('ratepay.logger');
        } else {
            // create a new logger as described in services/logging.xml
            $logger = new Logger(
                'ratepay',
                [new RotatingFileHandler(
                    $this->container->getParameter('kernel.logs_dir') . '/ratepay_' . $this->container->getParameter('kernel.environment') . '.log',
                    5,
                    Logger::DEBUG
                )],
                [new PsrLogMessageProcessor()]
            );
        }
        
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
