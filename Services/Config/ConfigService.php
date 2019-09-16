<?php


namespace RpayRatePay\Services\Config;


use Shopware\Models\Payment\Payment;
use Shopware_Components_Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigService
{

    /**
     * @var Shopware_Components_Config
     */
    private $_config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $pluginName;

    public function __construct(
        ContainerInterface $container,
        Shopware_Components_Config $config,
        $pluginName
    )
    {
        $this->container = $container;
        $this->_config = $config;
        $this->pluginName = $pluginName;
    }

    public function getPluginVersion() {
        return $this->container->getParameter('active_plugins')[$this->pluginName];
    }

    public function getDfpSnippetId() {
        $defaultValue = 'ratepay';
        $value = $this->_config->get('ratepay/dfp/snippet_id', $defaultValue);
        return strlen($value) ? $value : $defaultValue;
    }

    public function getProfileId($countryISO, $shopId = null, $zeroPercent = false, $isBackend = false)
    {
        $profileIdBase = $this->_config->get($this->getProfileIdKey($countryISO, $isBackend), $shopId);
        return $zeroPercent ? $profileIdBase . '_0RT' : $profileIdBase;
    }
    public function getProfileIdKey($countryISO, $isBackend) {
        //ratepay/profile/de/frontend/id - this comment is just for finding this line ;-)
        return "ratepay/profile/".strtolower($countryISO)."/".($isBackend ? 'backend' : 'frontend')."/id";
    }

    public function getSecurityCode($countryISO, $shopId, $isBackend = false)
    {
        return $this->_config->get($this->getSecurityCodeKey($countryISO, $isBackend), $shopId);
    }
    public function getSecurityCodeKey($countryISO, $isBackend) {
        //ratepay/profile/de/frontend/security_code - this comment is just for finding this line ;-)
        return "ratepay/profile/".strtolower($countryISO)."/".($isBackend ? 'backend' : 'frontend')."/security_code";
    }

    /**
     * @param null $shopId
     * @return bool
     */
    public function isCommitDiscountAsCartItem($shopId = null) {
        return $this->_config->get('ratepay/advanced/use_fallback_discount_item', $shopId) == 1;
    }

    /**
     * @param null $shopId
     * @return bool
     */
    public function isCommitShippingAsCartItem($shopId = null) {
        return $this->_config->get('ratepay/advanced/use_fallback_shipping_item', $shopId) == 1;
    }

    public function isBidirectionalEnabled()
    {
        return $this->_config->get('ratepay/bidirectional/enable') == 1;
    }

    public function getConfig($configName, $default = null)
    {
        return $this->_config->get($configName, $default);
    }

    /**
     * @param $paymentMethod
     * @param null $shopId
     * @return int
     */
    public function getPaymentStatusAfterPayment($paymentMethod, $shopId = null)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return $this->_config->get('ratepay/status/'.$paymentMethod, $shopId) == 1;
    }
}
