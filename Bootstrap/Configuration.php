<?php

namespace RpayRatePay\Bootstrap;

use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Config\WriterService;
use RpayRatePay\Services\Logger\RequestLogger;
use RpayRatePay\Services\PaymentMethodsService;
use RpayRatePay\Services\Request\ProfileRequestService;

class Configuration extends AbstractBootstrap
{

    /**
     * @var PaymentMethodsService
     */
    protected $paymentMethodsService;
    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->paymentMethodsService = new PaymentMethodsService($this->modelManager, $this->installContext->getPlugin()->getName());
        $configService = new ConfigService(
            $this->container,
            $this->container->get('shopware.plugin.cached_config_reader'),
            $this->modelManager,
            $this->installContext->getPlugin()->getName(),
            $this->updateContext ? $this->updateContext->getUpdateVersion() : $this->installContext->getPlugin()->getVersion()
        );
        $configWriter = new WriterService( /// TODO uhhh - that's not soo beautiful
            $this->modelManager,
            new ProfileRequestService(
                $db = $this->container->get('db'),
                $configService,
                new RequestLogger(
                    $configService,
                    $this->modelManager,
                    $this->logger
                )
            ),
            $this->logger
        );
        $this->profileConfigService = new ProfileConfigService($this->modelManager, $configService, $configWriter, $this->logger);
    }

    public function install()
    {
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function update()
    {
        if ($this->updateContext === null && $this->installContext->getPlugin()->getActive() == false) {
            return;
        }
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function uninstall($keepUserData = false)
    {
        //do nothing
    }

    public function activate()
    {
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function deactivate()
    {
        //do nothing
    }
}
