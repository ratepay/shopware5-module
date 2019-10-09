<?php


namespace RpayRatePay\Services\Config;


use RuntimeException;
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

    public function getPluginVersion()
    {
        return $this->container->getParameter('active_plugins')[$this->pluginName];
    }

    public function getDfpSnippetId()
    {
        $defaultValue = 'ratepay';
        $value = $this->_config->get('ratepay/dfp/snippet_id', $defaultValue);
        return strlen($value) ? $value : $defaultValue;
    }

    public function getProfileId($countryISO, $zeroPercentPayment = false, $isBackend = false)
    {
        return $this->_config->get($this->getProfileIdKey($countryISO, $zeroPercentPayment, $isBackend), null);
    }

    public function getProfileIdKey($countryISO, $zeroPercentPayment, $isBackend)
    {
        //ratepay/profile/de/frontend/id - this comment is just for finding this line ;-)
        //ratepay/profile/de/frontend/id/installment0 - this comment is just for finding this line ;-)
        return "ratepay/profile/" . strtolower($countryISO) . "/" . ($isBackend ? 'backend' : 'frontend') . "/id".($zeroPercentPayment ? "/installment0" : "");
    }

    public function getSecurityCode($countryISO, $zeroPercentPayment, $isBackend = false)
    {
        return $this->_config->get($this->getSecurityCodeKey($countryISO, $zeroPercentPayment, $isBackend), null);
    }

    public function getSecurityCodeKey($countryISO, $zeroPercentPayment, $isBackend)
    {
        //ratepay/profile/de/frontend/security_code - this comment is just for finding this line ;-)
        return "ratepay/profile/" . strtolower($countryISO) . "/" . ($isBackend ? 'backend' : 'frontend') . "/security_code" . "/id".($zeroPercentPayment ? "/installment0" : "");
    }

    /**
     * @return bool
     */
    public function isCommitDiscountAsCartItem()
    {
        return $this->_config->get('ratepay/advanced/use_fallback_discount_item', null) == 1;
    }

    /**
     * @return bool
     */
    public function isCommitShippingAsCartItem()
    {
        return $this->_config->get('ratepay/advanced/use_fallback_shipping_item', null) == 1;
    }

    /**
     * boolean: `false` call the delivery only if all items has been delivered (or canceled)
     * so in the last deliver request, all items must be placed in the basket
     *
     * @return bool
     */
    public function isInstallmentDirectDelivery() {
        return $this->_config->get('ratepay/advanced/installment_direct_delivery', null) == 1;
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
     * @return int
     */
    public function getPaymentStatusAfterPayment($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return $this->_config->get('ratepay/status/' . $paymentMethod, null);
    }

    public function getBidirectionalOrderStatus($action)
    {
        $allowedActions = ['full_delivery', 'full_cancellation', 'full_return'];
        if (!in_array($action, $allowedActions)) {
            throw new RuntimeException('Just these actions are allowed: ' . implode(',', $allowedActions));
        }
        return intval($this->_config->get('ratepay/bidirectional/status/' . $action, null));
    }
}
