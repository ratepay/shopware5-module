<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Config;


use InvalidArgumentException;
use RpayRatePay\Enum\PaymentMethods;
use RuntimeException;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigService
{

    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var string
     */
    protected $pluginName;
    /**
     * @var ConfigReader
     */
    private $configReader;
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * this field got filled by Bootstrap\Configuration
     * @var string
     */
    private $updateVersion;

    public function __construct(
        ContainerInterface $container,
        ConfigReader $configReader,
        ModelManager $modelManager,
        $pluginName,
        $updateVersion = null
    )
    {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->pluginName = $pluginName;
        $this->updateVersion = $updateVersion;
    }

    public function getPluginVersion()
    {
        return $this->updateVersion ?: $this->container->getParameter('active_plugins')[$this->pluginName];
    }

    public function getDfpSnippetId($shopId = null)
    {
        $defaultValue = 'ratepay';
        $value = $this->getConfig('ratepay/dfp/snippet_id', $defaultValue, $shopId);
        return strlen($value) ? $value : $defaultValue;
    }

    /**
     * @param $configName
     * @param null $default
     * @param Shop|int $shop
     * @return mixed
     */
    public function getConfig($configName, $default = null, $shop = null)
    {
        if ($shop === null) {
            //$shop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
        } else if (is_numeric($shop)) {
            $shop = $this->modelManager->find(Shop::class, $shop);
            if ($shop === null) {
                throw new InvalidArgumentException('the given shop does not exist');
            }
        } else if ($shop instanceof Shop === false) {
            throw new InvalidArgumentException('please provide a valid shop parameter (Shop-Model, Shop-Id or NULL for the default default)');
        }
        $config = $this->configReader->getByPluginName($this->pluginName, $shop);
        return isset($config[$configName]) ? $config[$configName] : $default;
    }

    /**
     * @param $countryISO
     * @param bool $zeroPercentPayment
     * @param bool $isBackend
     * @param Shop|int $shop
     * @return mixed
     */
    public function getProfileId($countryISO, $zeroPercentPayment = false, $isBackend = false, $shop = null)
    {
        return $this->getConfig($this->getProfileIdKey($countryISO, $zeroPercentPayment, $isBackend), null, $shop);
    }

    public function getProfileIdKey($countryISO, $zeroPercentPayment, $isBackend)
    {
        //ratepay/profile/de/frontend/id - this comment is just for finding this line ;-)
        //ratepay/profile/de/frontend/id/installment0 - this comment is just for finding this line ;-)
        return "ratepay/profile/" . strtolower($countryISO) . "/" . ($isBackend ? 'backend' : 'frontend') . "/id" . ($zeroPercentPayment ? "/installment0" : "");
    }

    /**
     * @param $countryISO
     * @param $zeroPercentPayment
     * @param bool $isBackend
     * @param Shop|int $shop
     * @return mixed
     */
    public function getSecurityCode($countryISO, $zeroPercentPayment, $isBackend = false, $shop = null)
    {
        return $this->getConfig($this->getSecurityCodeKey($countryISO, $zeroPercentPayment, $isBackend), null, $shop);
    }

    public function getSecurityCodeKey($countryISO, $zeroPercentPayment, $isBackend)
    {
        //ratepay/profile/de/frontend/security_code - this comment is just for finding this line ;-)
        return "ratepay/profile/" . strtolower($countryISO) . "/" . ($isBackend ? 'backend' : 'frontend') . "/security_code" . "/id" . ($zeroPercentPayment ? "/installment0" : "");
    }

    /**
     * @return bool
     */
    public function isCommitDiscountAsCartItem()
    {
        //this is a global configuration
        return $this->getConfig('ratepay/advanced/use_fallback_discount_item', null, null) == 1;
    }

    /**
     * @return bool
     */
    public function isCommitShippingAsCartItem()
    {
        //this is a global configuration
        return $this->getConfig('ratepay/advanced/use_fallback_shipping_item', null, null) == 1;
    }

    /**
     * boolean: `false` call the delivery only if all items has been delivered (or canceled)
     * so in the last deliver request, all items must be placed in the basket
     *
     * @return bool
     */
    public function isInstallmentDirectDelivery()
    {
        //this is a global configuration
        return $this->getConfig('ratepay/advanced/installment_direct_delivery', null, null) == 1;
    }

    public function isBidirectionalEnabled($type = 'order')
    {
        //this is a global configuration
        if ($type === 'order') {
            return $this->getConfig('ratepay/bidirectional/enable', null, null) == 1;
        } else if ($type === 'position') {
            return $this->getConfig('ratepay/bidirectional/position/enable', null, null) == 1;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEsdAutoDeliver()
    {
        return $this->getConfig('ratepay/advanced/esd_auto_delivery', 1, null) == 1;
    }

    public function getAdditionalAddressLineSetting()
    {
        return $this->getConfig('ratepay/advanced/additional_address_line_config', 'concat', null);
    }

    /**
     * @param $paymentMethod
     * @param Shop|int $shop
     * @return int
     */
    public function getPaymentStatusAfterPayment($paymentMethod, $shop)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return $this->getConfig('ratepay/status/' . $paymentMethod, null, $shop);
    }

    public function getBidirectionalOrderStatus($action)
    {
        $allowedActions = ['full_delivery', 'full_cancellation', 'full_return'];
        if (!in_array($action, $allowedActions)) {
            throw new RuntimeException('Just these actions are allowed: ' . implode(',', $allowedActions));
        }
        return (int)$this->getConfig('ratepay/bidirectional/status/' . $action, null);
    }

    public function getBidirectionalPositionStatus($action)
    {
        $allowedActions = ['full_delivery', 'full_cancellation', 'full_return'];
        if (!in_array($action, $allowedActions)) {
            throw new RuntimeException('Just these actions are allowed: ' . implode(',', $allowedActions));
        }
        return (int)$this->getConfig('ratepay/bidirectional/position/status/' . $action, null);
    }

    public function getTrackingCodeSeparator()
    {
        $separator = $this->getConfig('ratepay/advanced/tracking_separator', null);
        return !empty($separator) ? $separator : null;
    }

    /**
     * Product Page: is Installment Calculator enabled?
     * @return bool
     */
    public function isPicEnabled()
    {
        return (bool) $this->getConfig('ratepay/detailInstallmentCalculator/enabled', false);
    }

    /**
     * Product Page Installment Calculator: enabled payment method
     * @return string
     */
    public function getPicPaymentMethod()
    {
        return $this->getConfig('ratepay/detailInstallmentCalculator/paymentMethod', PaymentMethods::PAYMENT_RATE);
    }

    /**
     * Product Page Installment Calculator: default billing country
     * @return string
     */
    public function getPicDefaultBillingCountry()
    {
        return $this->getConfig('ratepay/detailInstallmentCalculator/defaultBillingCountry', 'DE');
    }

    /**
     * Product Page Installment Calculator: default shipping country
     * @return string
     */
    public function getPicDefaultShippingCountry()
    {
        return $this->getConfig('ratepay/detailInstallmentCalculator/defaultShippingCountry', 'DE');
    }

    /**
     * Product Page Installment Calculator: default b2b enabled
     * @return bool
     */
    public function getPicDefaultB2b()
    {
        return (bool) $this->getConfig('ratepay/detailInstallmentCalculator/defaultB2b', false);
    }


    /**
     * @return array
     */
    public function getEnabledFeatures()
    {
        $flags = $this->getConfig('ratepay/advanced/feature_flags') ?: '';
        return array_map(static function ($value) {
            return trim($value);
        }, explode(',', $flags));
    }

    /**
     * @deprecated will be removed within the next releases
     */
    public function getAllProfileConfigs(Shop $shop)
    {
        return [];
    }
}
