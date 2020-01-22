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
        return 'RatePay Payment';
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

        Logger::singleton()->info('RatePAY: event subscription');
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

        Logger::singleton()->info('RatePAY: bootstrap routines');
        foreach ($queue as $bootstrapper) {
            $bootstrapper->install();
            Logger::singleton()->info('[OK] ' . get_class($bootstrapper));
        }

        $this->Plugin()->setActive(true);
        Logger::singleton()->info('RatePAY: Successful module installation');

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

        Logger::singleton()->info('RatePAY: bootstrap routines');
        foreach ($queue as $bootstrapper) {
            $bootstrapper->update();
            Logger::singleton()->info('[OK] ' . get_class($bootstrapper));
        }

        Logger::singleton()->info('RatePAY: Successful module update');
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
            new \RpayRatePay\Bootstrapping\Events\BogxProductConfiguratorSubscriber()
        ];

        $eventManager = Shopware()->Events();

        foreach ($subscribers as $subscriber) {
            $eventManager->addSubscriber($subscriber);
//            Logger::singleton()->info('[OK] ' . get_class($subscriber));
        }
    }
}
