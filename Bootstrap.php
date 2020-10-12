<?php

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Service\Logger;

require_once __DIR__ . '/Component/CSRFWhitelistAware.php';

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    private $str;

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
    public function getLabel()
    {
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

        Logger::singleton()->info('INSTALL Plugin Bootstrap');

        Logger::singleton()->info('Ratepay: event subscription');
        $this->subscribeEvent(
            'Enlight_Controller_Front_StartDispatch',
            'onRegisterSubscriber'
        );
        $this->subscribeEvent(
            'Shopware_Console_Add_Command',
            'onRegisterSubscriber'
        );

        $queue = [
            new \RpayRatePay\Bootstrapping\PaymentsSetup($this),
            new \RpayRatePay\Bootstrapping\FormsSetup($this),
            new \RpayRatePay\Bootstrapping\TranslationsSetup($this),
            new \RpayRatePay\Bootstrapping\MenuesSetup($this),
            new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
            new \RpayRatePay\Bootstrapping\PaymentStatusesSetup($this),
            new \RpayRatePay\Bootstrapping\DeliveryStatusesSetup($this),
            new \RpayRatePay\Bootstrapping\CronjobSetup($this),
            new \RpayRatePay\Bootstrapping\AdditionalOrderAttributeSetup($this),
            new \RpayRatePay\Bootstrapping\UserAttributeSetup($this)
        ];

        Logger::singleton()->info('Ratepay: bootstrap routines');
        foreach ($queue as $bootstrapper) {
            $bootstrapper->install();
            Logger::singleton()->info('[OK] ' . get_class($bootstrapper));
        }

        $this->Plugin()->setActive(true);
        Logger::singleton()->info('Ratepay: Successful module installation');

        return [
            'success' => true,
            'invalidateCache' => [
                'frontend',
                'backend'
            ]
        ];
    }

    /**
     * Updates the Plugin and its components
     *
     * @param string $version
     * @return array|bool
     * @throws exception
     * @todo: implement translation update while updating
     */
    public function update($version)
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_StartDispatch',
            'onRegisterSubscriber'
        );
        $this->subscribeEvent(
            'Shopware_Console_Add_Command',
            'onRegisterSubscriber'
        );

        Logger::singleton()->info('UPDATE Plugin Bootstrap ' . $version);
        $queue = [
            new \RpayRatePay\Bootstrapping\FormsSetup($this),
            new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
            new \RpayRatePay\Bootstrapping\TranslationsSetup($this),
            new \RpayRatePay\Bootstrapping\PaymentsSetup($this),
            new \RpayRatePay\Bootstrapping\ShopConfigSetup($this),
            new \RpayRatePay\Bootstrapping\CronjobSetup($this),
            new \RpayRatePay\Bootstrapping\AdditionalOrderAttributeSetup($this),
            new \RpayRatePay\Bootstrapping\UserAttributeSetup($this)
        ];

        $this->_dropOrderAdditionalAttributes();

        Logger::singleton()->info('Ratepay: bootstrap routines');
        foreach ($queue as $bootstrapper) {
            $bootstrapper->update();
            Logger::singleton()->info('[OK] ' . get_class($bootstrapper));
        }

        Logger::singleton()->info('Ratepay: Successful module update');
        Logger::singleton()->addNotice('Successful module update');

        return [
            'success' => true,
            'invalidateCache' => ['frontend', 'backend']
        ];
    }

    /**
     * drops additional attributes for ratepay orders in s_order_attributes
     */
    public function _dropOrderAdditionalAttributes()
    {
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        Shopware()->Models()->generateAttributeModels(
            ['s_order_attributes']
        );
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
            new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
            new \RpayRatePay\Bootstrapping\UserAttributeSetup($this)
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
            new \RpayRatePay\Bootstrapping\Events\OrderOperationsSubscriber(),
            new \RpayRatePay\Bootstrapping\Events\TemplateExtensionSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\PaymentControllerSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\LoggingControllerSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\OrderDetailControllerSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\CheckoutValidationSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\PaymentFilterSubscriber(),
            new \RpayRatePay\Bootstrapping\Events\PluginConfigurationSubscriber($this->getName()),
            new \RpayRatePay\Bootstrapping\Events\OrderDetailsProcessSubscriber(),
            new \RpayRatePay\Bootstrapping\Events\JavascriptSourceSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\OrderViewExtensionSubscriber($this->Path()),
            new \RpayRatePay\Bootstrapping\Events\UpdateTransactionsSubscriber(),
            new \RpayRatePay\Bootstrapping\Events\BackendOrderControllerSubscriber(new \RpayRatePay\Component\Service\ConfigLoader($this->get('db')), $this->Path()),
            new \RpayRatePay\Bootstrapping\Events\BackendOrderViewExtensionSubscriber($this->Path()),
        ];

        foreach ($subscribers as $subscriber) {
            Shopware()->Events()->addSubscriber($subscriber);
//            Logger::singleton()->info('[OK] ' . get_class($subscriber));
        }
    }
}
