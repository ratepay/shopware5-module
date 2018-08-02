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
            $this->get('Loader')->registerNamespace(
                'RpayRatePay\\Bootstrapping', $this->Path() . 'Bootstrapping/'
            );

            $this->get('Loader')->registerNamespace(
                'RatePAY', $this->Path() . 'Component/Library/src/'
            );
        }

        /**
         * Returns the Plugin version
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
                new \RpayRatePay\Bootstrapping\PaymentsSetup($this),
                new \RpayRatePay\Bootstrapping\FormsSetup($this),
                new \RpayRatePay\Bootstrapping\TranslationsSetup($this),
                new \RpayRatePay\Bootstrapping\MenuesSetup($this),
                new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
                new \RpayRatePay\Bootstrapping\PaymentStatusesSetup($this),
                new \RpayRatePay\Bootstrapping\DeliveryStatusesSetup($this),
                new \RpayRatePay\Bootstrapping\CronjobSetup($this),
                new \RpayRatePay\Bootstrapping\AdditionalOrderAttributeSetup($this),
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
                new \RpayRatePay\Bootstrapping\FormsSetup($this),
                new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
                new \RpayRatePay\Bootstrapping\TranslationsSetup($this),
                new \RpayRatePay\Bootstrapping\PaymentsSetup($this),
                new \RpayRatePay\Bootstrapping\ShopConfigSetup($this),
                new \RpayRatePay\Bootstrapping\CronjobSetup($this),
                new \RpayRatePay\Bootstrapping\AdditionalOrderAttributeSetup($this),
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
                new \RpayRatePay\Bootstrapping\DatabaseSetup($this),
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
            ];

            foreach ($subscribers as $subscriber) {
                Shopware()->Events()->addSubscriber($subscriber);
            }
        }
    }
