<?php

namespace RpayRatePay\Bootstrap;

use Monolog\Logger;
use RpayRatePay\Enum\Country;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\WriterService;
use RpayRatePay\Services\PaymentMethodsService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Configuration extends AbstractBootstrap
{

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var WriterService
     */
    protected $configWriter;
    /**
     * @var ConfigService
     */
    protected $config;

    public function __construct(
        InstallContext $context,
        ContainerInterface $container,
        ModelManager $modelManager,
        \Shopware_Components_Config $config,
        Logger $pluginLogger
    )
    {
        parent::__construct($context);
        $this->modelManager = $modelManager;
        $this->configWriter = new WriterService(
            $this->modelManager,
            new PaymentMethodsService($this->modelManager, $context->getPlugin()->getName()),
            $pluginLogger
        );
        $this->config = new ConfigService($container, $config, $context->getPlugin()->getName());
    }

    public function install()
    {
        //do nothing
    }

    public function update()
    {
        if ($this->installContext->getPlugin()->getActive() == false) {
            return;
        }

        $this->configWriter->truncateConfigTables();

        $repo = $this->modelManager->getRepository(Shop::class);
        $shops = $repo->findBy(['active' => true]);

        /** @var Shop $shop */
        foreach ($shops as $shop) {
            $this->updateRatepayConfig($shop->getId(), false);
            $this->updateRatepayConfig($shop->getId(), true);
        }
    }

    public function uninstall($keepUserData = false)
    {
        //do nothing
    }

    public function activate()
    {
        //do nothing
    }

    public function deactivate()
    {
        //do nothing
    }

    private function updateRatepayConfig($shopId, $backend)
    {
        foreach (Country::getCountries() as $iso) {
            $profileId = $this->config->getProfileId($iso, $shopId, false, $backend);
            $securityCode = $this->config->getSecurityCode($iso, $shopId, $backend);

            if (empty($profileId)) {
                continue;
            }

            $this->configWriter->writeRatepayConfig($profileId, $securityCode, $shopId, $iso, $backend);

            if ($iso == 'DE') {
                $profileIdZeroPercent = $this->config->getProfileId($iso, $shopId, true, $backend);
                $this->configWriter->writeRatepayConfig($profileIdZeroPercent, $securityCode, $shopId, $iso, $backend);
            }
        }
    }
}
