<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Bootstrapping\AdditionalOrderAttributeSetup;
use RpayRatePay\Bootstrapping\CronjobSetup;
use RpayRatePay\Bootstrapping\DatabaseSetup;
use RpayRatePay\Bootstrapping\DeliveryStatusesSetup;
use RpayRatePay\Bootstrapping\Events\AssetsSubscriber;
use RpayRatePay\Bootstrapping\Events\BackendOrderControllerSubscriber;
use RpayRatePay\Bootstrapping\Events\BackendOrderViewExtensionSubscriber;
use RpayRatePay\Bootstrapping\Events\BogxProductConfiguratorSubscriber;
use RpayRatePay\Bootstrapping\Events\CheckoutValidationSubscriber;
use RpayRatePay\Bootstrapping\Events\LoggingControllerSubscriber;
use RpayRatePay\Bootstrapping\Events\OrderDetailControllerSubscriber;
use RpayRatePay\Bootstrapping\Events\OrderDetailsProcessSubscriber;
use RpayRatePay\Bootstrapping\Events\OrderOperationsSubscriber;
use RpayRatePay\Bootstrapping\Events\OrderViewExtensionSubscriber;
use RpayRatePay\Bootstrapping\Events\PaymentControllerSubscriber;
use RpayRatePay\Bootstrapping\Events\PaymentFilterSubscriber;
use RpayRatePay\Bootstrapping\Events\PluginConfigurationSubscriber;
use RpayRatePay\Bootstrapping\Events\TemplateExtensionSubscriber;
use RpayRatePay\Bootstrapping\Events\UpdateTransactionsSubscriber;
use RpayRatePay\Bootstrapping\FormsSetup;
use RpayRatePay\Bootstrapping\MenuesSetup;
use RpayRatePay\Bootstrapping\PaymentsSetup;
use RpayRatePay\Bootstrapping\PaymentStatusesSetup;
use RpayRatePay\Bootstrapping\ShopConfigSetup;
use RpayRatePay\Bootstrapping\TranslationsSetup;
use RpayRatePay\Bootstrapping\UserAttributeSetup;
use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\Logger;

require_once __DIR__ . '/Component/CSRFWhitelistAware.php';

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    public static function getPaymentMethods()
    {
        return [
            'rpayratepayinvoice',
            'rpayratepayrate',
            'rpayratepaydebit',
            'rpayratepayrate0',
            'rpayratepayprepayment',
        ];
    }

    /**
     * Returns the Label of the Plugin
     *
     * @return string
     */
    public function getLabel() {
        return 'Ratepay Payment Plugin for Shopware 5';
    }

    public function afterInit()
    {
        $loader = $this->get('Loader');

        //Plugin
        $loader->registerNamespace('RpayRatePay', $this->Path() . '/');

        //library
        $loader->registerNamespace('RatePAY', $this->Path() . 'Component/Library/src/');
    }

    /**
     * Returns the Plugin version
     *
     * @return string
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new \Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns the PaymentConfirm Config
     *
     * @return mixed
     * @throws Exception
     */
    public static function getPCConfig()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['payment_confirm'];
        } else {
            throw new \Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns all allowed actions
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'install' => true,
            'update' => true,
            'enable' => true
        ];
    }

    /**
     * Installs the Plugin and its components
     *
     * @return array
     * @throws Exception
     */
    public function install()
    {
        parent::install();

        $this->subscribeEvent('Enlight_Controller_Front_StartDispatch', 'onRegisterSubscriber');
        $this->subscribeEvent('Shopware_Console_Add_Command', 'onRegisterSubscriber');

        $queue = [
            new PaymentsSetup($this),
            new FormsSetup($this),
            new DatabaseSetup($this),
            new TranslationsSetup($this),
            new MenuesSetup($this),
            new ShopConfigSetup($this),
            new CronjobSetup($this),
            new AdditionalOrderAttributeSetup($this),
            new PaymentStatusesSetup($this),
            new DeliveryStatusesSetup($this),
            new UserAttributeSetup($this)
        ];

        foreach ($queue as $bootstrapper) {
            $bootstrapper->install();
        }

        $this->Plugin()->setActive(true);

        return [
            'success' => true,
            'invalidateCache' => [
                'frontend',
                'backend'
            ]
        ];
    }

    /**
     * @param string $version
     * @return array|bool
     * @throws Exception
     */
    public function update($version)
    {
        Shopware()->Db()->executeUpdate("DELETE FROM `s_core_subscribes` WHERE `listener` LIKE '%".__CLASS__."::add%Files%'");
        return $this->install();
    }

    /**
     * Uninstalls the Plugin and its components
     *
     * @return array|bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function uninstall()
    {
        Logger::singleton()->info('UNINSTALL Plugin Bootstrap ');
        $queue = [
            new DatabaseSetup($this),
            new UserAttributeSetup($this)
        ];

        $this->disable();

        foreach ($queue as $bootstrap) {
            $bootstrap->uninstall();
        }

        return parent::uninstall();
    }

    /**
     * Deactivates the Plugin and its components
     *
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function disable()
    {
        $sql = "UPDATE `s_core_paymentmeans` SET `active` = 0 WHERE `name` LIKE 'rpayratepay%'";

        Shopware()->Db()->query($sql);

        return true;
    }

    public function onRegisterSubscriber()
    {
        $subscribers = [
            new OrderOperationsSubscriber(),
            new TemplateExtensionSubscriber($this->Path()),
            new PaymentControllerSubscriber($this->Path()),
            new LoggingControllerSubscriber($this->Path()),
            new OrderDetailControllerSubscriber($this->Path()),
            new CheckoutValidationSubscriber($this->Path()),
            new PaymentFilterSubscriber(),
            new PluginConfigurationSubscriber($this->getName()),
            new OrderDetailsProcessSubscriber(),
            new AssetsSubscriber($this->Path()),
            new OrderViewExtensionSubscriber($this->Path()),
            new UpdateTransactionsSubscriber(),
            new BackendOrderControllerSubscriber(new ConfigLoader($this->get('db')), $this->Path()),
            new BackendOrderViewExtensionSubscriber($this->Path()),
            new BogxProductConfiguratorSubscriber()
        ];

        $eventManager = Shopware()->Events();

        foreach ($subscribers as $subscriber) {
            $eventManager->addSubscriber($subscriber);
        }
    }

}
