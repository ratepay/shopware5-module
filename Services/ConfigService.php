<?php

namespace RpayRatePay\Services;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigService
{
    //TODO remove if plugin is moved to SW5.2 plugin engine
    private static $instance = null;
    /**
     * @var CachedConfigReader
     */
    protected $configReader;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public static function getInstance()
    {
        return self::$instance = (self::$instance ? : new self(Shopware()->Container()));
    }

    public function __construct(ContainerInterface $container)
    {
        $this->configReader = $container->get('shopware.plugin.cached_config_reader');
        $this->modelManager = $container->get('models');
    }

    /**
     * @param Shop|int $shop
     * @return |null
     */
    public function getDfpSnippetId($shop)
    {
        return $this->getConfig('ratepay/dfp/snippet_id', 'ratepay', $shop);
    }

    /**
     * @param string $countryCode
     * @param boolean $isBackend
     * @param boolean $zeroPercent
     * @param Shop|int $shop
     * @return |null |null
     */
    public function getProfileId($countryCode, $isBackend, $zeroPercent, $shop)
    {
        $key = 'RatePayProfileID';
        $key = $key . strtoupper($countryCode);
        $key = $key . ($isBackend ? 'Backend' : '');
        $key = $key . ($zeroPercent ? '_0RT' : '');
        return $this->getConfig($key, null, $shop);
    }
    /**
     * @param string $countryCode
     * @param boolean $isBackend
     * @param Shop|int $shop
     * @return |null |null
     */
    public function getSecurityCode($countryCode, $isBackend, $shop)
    {
        $key = 'RatePaySecurityCode';
        $key = $key . strtoupper($countryCode);
        $key = $key . ($isBackend ? 'Backend' : '');
        return $this->getConfig($key, null, $shop);
    }

    /**
     * @param string $configKey
     * @param null $default
     * @param Shop|int $shop
     * @return |null
     */
    public function getConfig($configKey, $default = null, $shop = null)
    {

        $config = $this->getAllConfig($shop);
        return isset($config[$configKey]) ? $config[$configKey] : $default;
    }

    /**
     * @param Shop|int $shop
     * @return array|false|mixed
     */
    public function getAllConfig($shop)
    {
        if (is_numeric($shop)) {
            $shop = $this->modelManager->find(Shop::class, $shop);
            if ($shop == null) {
                throw new \InvalidArgumentException('shop cannot be found');
            }
        }
        return $this->configReader->getByPluginName('RpayRatePay', $shop);
    }
}
