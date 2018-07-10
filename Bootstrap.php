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
    require_once __DIR__ . '/Component/CSRFWhitelistAware.php';

    class Shopware_Plugins_Frontend_RpayRatePay_Bootstrap extends Shopware_Components_Plugin_Bootstrap
    {

        public static function getPaymentMethods() {
            return array(
                'rpayratepayinvoice',
                'rpayratepayrate',
                'rpayratepaydebit',
                'rpayratepayrate0',
            );
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
         * Returns the Pluginversion
         *
         * @return string
         * @throws Exception
         */
        public function getVersion()
        {
            $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'plugin.json'), true);
            if ($info) {
                return $info['currentVersion'];
            } else {
                throw new Exception('The plugin has an invalid version file.');
            }
        }

        /**
         * Returns the PaymentConfirm Config
         *
         * @return mixed
         * @throws Exception
         */
        public function getPCConfig()
        {
            $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'plugin.json'), true);
            if ($info) {
                return $info['payment_confirm'];
            } else {
                throw new Exception('The plugin has an invalid version file.');
            }
        }

        /**
         * Returns all allowed actions
         *
         * @return array
         */
        public function getCapabilities()
        {
            return array(
                'install' => true,
                'update'  => true,
                'enable'  => true
            );
        }

        /**
         * Installs the Plugin and its components
         *
         * @return array
         * @throws Exception
         */
        public function install()
        {
            $this->subscribeEvent('Enlight_Controller_Front_StartDispatch', 'onRegisterSubscriber');
            $this->subscribeEvent('Shopware_Console_Add_Command', 'onRegisterSubscriber');

            $queue = [
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_PaymentsSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_FormsSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_TranslationsSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_MenuesSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_DatabaseSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_PaymentStatusesSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_DeliveryStatusesSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_CronjobSetup($this),
            ];

            foreach ($queue as $bootstrapper) {
                $bootstrapper->install();
            }

            $this->Plugin()->setActive(true);

            return array(
                'success' => true,
                'invalidateCache' => array('frontend', 'backend')
            );
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
            $queue = [
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_FormsSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_DatabaseSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_TranslationsSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_PaymentsSetup($this),
                //new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_ShopConfigSetup($this),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_CronjobSetup($this),
            ];

            $this->_dropOrderAdditionalAttributes();

            foreach ($queue as $bootstrapper) {
                $bootstrapper->update();
            }

            Shopware()->PluginLogger()->addNotice('Successful module update');

            return array(
                'success' => true,
                'invalidateCache' => array('frontend', 'backend')
            );
        }

        /**
        * drops additional attributes for ratepay orders in s_order_attributes
        */
        public function _dropOrderAdditionalAttributes()
        {
            $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
            $metaDataCache->deleteAll();
            Shopware()->Models()->generateAttributeModels(
                array('s_order_attributes')
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
            $queue = [
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_DatabaseSetup($this),
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
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderOperationsSubscriber(),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_TemplateExtensionSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_LoggingControllerSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailControllerSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_CheckoutValidationSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentFilterSubscriber(),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PluginConfigurationSubscriber($this->getName()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailsProcessSubscriber(),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_JavascriptSourceSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderViewExtensionSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_UpdateTransactionsSubscriber(),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_BackendOrderControllerSubscriber($this->Path()),
                new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_BackendOrderViewExtensionSubscriber($this->Path()),
            ];

            foreach ($subscribers as $subscriber) {
                Shopware()->Events()->addSubscriber($subscriber);
            }
        }

        /**
         * @return bool
         */
        public function isSWAGBackendOrdersActive()
        {
            $sql = "SELECT id FROM s_core_plugins WHERE `name`='SWAGBackendOrder' AND active=1";
            $result = Shopware()->Db()->fetchOne($sql);
            if (empty($result)) {
                return false;
            } else {
                return true;
            }
        }

    }
