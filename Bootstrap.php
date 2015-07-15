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
    class Shopware_Plugins_Frontend_RpayRatePay_Bootstrap extends Shopware_Components_Plugin_Bootstrap
    {

        /**
         * Get Info for the Pluginmanager
         *
         * @return array
         */
        public function getInfo()
        {
            return array(
                'version'     => $this->getVersion(),
                'author'      => 'RatePay GmbH',
                'source'      => $this->getSource(),
                'supplier'    => 'RatePAY GmbH',
                'support'     => 'https://www.ratepay.com/service-center-haendler',
                'link'        => 'https://www.ratepay.com/',
                'copyright'   => 'Copyright (c) 2014, RatePAY GmbH',
                'label'       => 'RatePAY Payment',
                'description' =>
                    '<h2>RatePAY Payment plugin for Shopware Community Edition Version 5.0.0</h2>'
                    . '<ul>'
                    . '<li style="list-style: inherit;">RatePAY Payment Module</li>'
                    . '<li style="list-style: inherit;">Payment means: Invoice, Direct Debit (ELV), Rate</li>'
                    . '<li style="list-style: inherit;">Cancellations, Returns, etc. can be created from an additional tab in the order detail view</li>'
                    . '<li style="list-style: inherit;">Integrated support for multishops</li>'
                    . '<li style="list-style: inherit;">Improved payment form with visual feedback for your customers</li>'
                    . '<li style="list-style: inherit;">Supported Languages: German, English</li>'
                    . '<li style="list-style: inherit;">Backend Log with custom View accessible from your shop backend</li>'
                    . '</ul>'
            );
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
         * Returns the Label of the Plugin
         *
         * @return string
         */
        public function getLabel()
        {
            return 'RatePay Payment';
        }


        /**
         * Returns the Pluginversion
         *
         * @return string
         */
        public function getVersion()
        {
            return "4.0.3";
        }

        /**
         * Installs the Plugin and its components
         *
         * @return boolean
         */
        public function install()
        {
            $this->_createPaymentmeans();
            $this->_createForm();
            $this->_createPluginConfigTranslation();
            $this->_subscribeEvents();
            $this->_createMenu();
            $this->_createDataBaseTables();
            $this->_createPaymentStati();
            $this->_createDeliveryStati();
            $this->_createExtraFields();
            $this->Plugin()->setActive(true);

            return array('success' => true, 'invalidateCache' => array('frontend', 'backend'));
        }

        /**
         * creates ratepay delivery stati
         */
        public function _createDeliveryStati()
        {
            $sql = "INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?";
            try {
                Shopware()->Db()->query($sql, array(
                    255, 'Teil-(Retoure)', 255, 'state', 0
                ));
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->addNotice('RatePAY', $exception->getMessage());
            }
            try {
                Shopware()->Db()->query($sql, array(
                    265, 'Teil-(Storno)', 265, 'state', 0
                ));
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->addNotice('RatePAY', $exception->getMessage());
            }
        }
        /**
         * creates ratepay payment stati
         */
        public function _createPaymentStati()
        {
            $sql = "INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?";
            try {
                Shopware()->Db()->query($sql, array(
                    155, 'Zahlungsabwicklung durch RatePAY', 155, 'payment', 0
                ));
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->addNotice('RatePAY', $exception->getMessage());
            }
        }

        /**
         * creates extra fields for ratepay orders in s_order_attributes
         */
        public function _createExtraFields()
        {
            Shopware()->Models()->addAttribute('s_order_attributes','RatePAY','ShopId','int(5)', false, 0);
            Shopware()->Models()->addAttribute('s_order_attributes','RatePAY','TransactionId','varchar(255)', false, 0);
            Shopware()->Models()->addAttribute('s_order_attributes','RatePAY','DgNumber','varchar(255)', false, 0);
            $metaDataCache  = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
            $metaDataCache->deleteAll();
            Shopware()->Models()->generateAttributeModels(
                array('s_order_attributes')
            );
        }

        /**
         * Updates the Plugin and its components
         *
         * @param string $version
         */
        public function update($version)
        {
            $this->_subscribeEvents();
            $this->_createForm();
            $this->_createPluginConfigTranslation();

            switch($version) {
                case '3.2.2':
                    $this->uninstall();
                    $sql = 'ALTER TABLE `rpay_ratepay_config` DROP PRIMARY KEY;';
                    Shopware()->Db()->query($sql);
                    $sql = 'ALTER TABLE `rpay_ratepay_config` ADD `shopId` INT(5) NOT NULL ;';
                    Shopware()->Db()->query($sql);
                    $sql = 'ALTER TABLE `rpay_ratepay_config` ADD PRIMARY KEY (`profileId`,`shopId`);';
                    Shopware()->Db()->query($sql);

                    //create extra fields
                    $this->_createExtraFields();
            }

            return array('success' => true, 'invalidateCache' => array('frontend', 'backend'));
        }


        /**
         * Uninstalls the Plugin and its components
         *
         * @return boolean
         */
        public function uninstall()
        {
            $this->disable();
            return parent::uninstall();
        }

        /**
         * Deactivates the Plugin and its components
         *
         * @return boolean
         */
        public function disable()
        {
            $sql = "UPDATE `s_core_paymentmeans` SET `active` = 0 WHERE `name` LIKE 'rpayratepay%'";
            Shopware()->Db()->query($sql);

            return true;
        }

        /**
         * @param $iso
         *
         * @return null|\Shopware\Models\Country\Country
         */
        final private function getCountry($iso)
        {
            return Shopware()->Models()->getRepository('Shopware\Models\Country\Country')
                             ->findOneBy(array('iso' => $iso));
        }

        /**
         * Creates the Paymentmeans
         */
        private function _createPaymentmeans()
        {
            try {
                $this->createPayment(
                    array(
                        'name'                  => 'rpayratepayinvoice',
                        'description'           => 'RatePAY Rechnung',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 1,
                        'additionaldescription' => 'Kauf auf Rechnung',
                        'template'              => 'RatePAYInvoice.tpl',
                        'pluginID'              => $this->getId(),
                        /*'countries'             => array(
                            $this->getCountry('DE'),
                            $this->getCountry('AT')
                        )*/
                    )
                );
                $this->createPayment(
                    array(
                        'name'                  => 'rpayratepayrate',
                        'description'           => 'RatePAY Ratenzahlung',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 2,
                        'additionaldescription' => 'Kauf mit Ratenzahlung',
                        'template'              => 'RatePAYRate.tpl',
                        'pluginID'              => $this->getId(),
                        /*'countries'             => array(
                            $this->getCountry('DE'),
                            $this->getCountry('AT')
                        )*/
                    )
                );
                $this->createPayment(
                    array(
                        'name'                  => 'rpayratepaydebit',
                        'description'           => 'RatePAY SEPA-Lastschrift',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 3,
                        'additionaldescription' => 'Kauf mit SEPA Lastschrift',
                        'template'              => 'RatePAYDebit.tpl',
                        'pluginID'              => $this->getId(),
                        /*'countries'             => array(
                            $this->getCountry('DE'),
                            $this->getCountry('AT')
                        )*/
                    )
                );
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception("Can not create payment." . $exception->getMessage());
            }
        }

        /**
         * Creates the Pluginconfiguration
         */
        private function _createForm()
        {
            try {
                $form = $this->Form();

                /** DE CREDENTIALS **/
                $form->setElement('button', 'button0', array(
                    'label' => '<b>Zugangsdaten für Deutschland:</b>',
                    'value' => ''
                ));
                $form->setElement('text', 'RatePayProfileIDDE', array(
                    'label' => 'Deutschland Profile-ID',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                $form->setElement('text', 'RatePaySecurityCodeDE', array(
                    'label' => 'Deutschland Security Code',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                ));
                $form->setElement('checkbox', 'RatePaySandboxDE', array(
                    'label' => 'Testmodus aktivieren ( Test Gateway )',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                ));


                /** AT CREDENTIALS **/
                $form->setElement('button', 'button1', array(
                    'label' => '<b>Zugangsdaten für Österreich:</b>',
                    'value' => ''
                ));
                $form->setElement('text', 'RatePayProfileIDAT', array(
                    'label' => 'Österreich Profile-ID',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                $form->setElement('text', 'RatePaySecurityCodeAT', array(
                    'label' => 'Österreich Security Code',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));
                $form->setElement('checkbox', 'RatePaySandboxAT', array(
                    'label' => 'Testmodus aktivieren ( Test Gateway )',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                ));

            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception("Can not create config elements." . $exception->getMessage());
            }
        }

        /**
         * Creates the Translation for the Pluginconfiguration
         */
        private function _createPluginConfigTranslation()
        {
            try {
                $form = $this->Form();
                $translations = array(
                    'de_DE' => array(
                        'RatePayProfileIDDE'    => 'Deutschland Profile-ID',
                        'RatePaySecurityCodeDE' => 'Deutschland Security Code',
                        'RatePayProfileIDAT'    => 'Österreich Profile-ID',
                        'RatePaySecurityCodeAT' => 'Österreich Security Code',
                        'RatePaySandboxDE'      => 'Testmodus aktivieren ( Test Gateway )',
                        'button0'               => 'Zugangsdaten für Deutschland',
                        'button1'               => 'Zugangsdaten für Österreich',
                    ),
                    'en_EN' => array(
                        'RatePayProfileIDDE'    => 'Profile-ID for Germany',
                        'RatePaySecurityCodeDE' => 'Security Code for Germany',
                        'RatePayProfileIDAT'    => 'Profile-ID for Austria',
                        'RatePaySecurityCodeAT' => 'Security Code for Austria',
                        'RatePaySandboxDE'      => 'Sandbox ( Test Gateway )',
                        'button0'               => 'Credentials for Germany',
                        'button1'               => 'Credentials for Austria',
                    )
                );

                $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
                foreach ($translations as $locale => $snippets) {
                    $localeModel = $shopRepository->findOneBy(array(
                        'locale' => $locale
                    ));
                    foreach ($snippets as $element => $snippet) {
                        if ($localeModel === null) {
                            continue;
                        }
                        $elementModel = $form->getElement($element);
                        if ($elementModel === null) {
                            continue;
                        }
                        $translationModel = new \Shopware\Models\Config\ElementTranslation();
                        $translationModel->setLabel($snippet);
                        $translationModel->setLocale($localeModel);
                        $elementModel->addTranslation($translationModel);
                    }
                }
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception("Can not create translation." . $exception->getMessage());
            }
        }

        /**
         * Creates the Databasetables
         *
         * @throws Exception SQL-Error
         */
        private function _createDataBaseTables()
        {
            $sqlLogging = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_logging` (" .
                          "`id` int(11) NOT NULL AUTO_INCREMENT," .
                          "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP," .
                          "`version` varchar(10) DEFAULT 'N/A'," .
                          "`operation` varchar(255) DEFAULT 'N/A'," .
                          "`suboperation` varchar(255) DEFAULT 'N/A'," .
                          "`transactionId` varchar(255) DEFAULT 'N/A'," .
                          "`firstname` varchar(255) DEFAULT 'N/A'," .
                          "`lastname` varchar(255) DEFAULT 'N/A'," .
                          "`request` text," .
                          "`response` text," .
                          "PRIMARY KEY (`id`)" .
                          ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlBankdata = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_user_bankdata` (" .
                           "`userID` int(11) NOT NULL AUTO_INCREMENT," .
                           "`bankname` varchar(255) NOT NULL," .
                           "`bankcode` varchar(255) NOT NULL," .
                           "`bankholder` varchar(255) NOT NULL," .
                           "`account` varchar(255) NOT NULL," .
                           "`iban` varchar(255) NOT NULL," .
                           "`bic` varchar(255) NOT NULL," .
                           "PRIMARY KEY (`userID`)" .
                           ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlConfig = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config` (" .
                         "`profileId` varchar(255) NOT NULL," .
                         "`shopId` int(5) NOT NULL, " .
                         "`invoiceStatus` int(1) NOT NULL, " .
                         "`debitStatus` int(1) NOT NULL, " .
                         "`rateStatus` int(1) NOT NULL, " .
                         "`b2b-invoice` varchar(3) NOT NULL, " .
                         "`b2b-debit` varchar(3) NOT NULL, " .
                         "`b2b-rate` varchar(3) NOT NULL, " .
                         "`address-invoice` varchar(3) NOT NULL, " .
                         "`address-debit` varchar(3) NOT NULL, " .
                         "`address-rate` varchar(3) NOT NULL, " .
                         "`limit-invoice-min` int(5) NOT NULL, " .
                         "`limit-debit-min` int(5) NOT NULL, " .
                         "`limit-rate-min` int(5) NOT NULL, " .
                         "`limit-invoice-max` int(5) NOT NULL, " .
                         "`limit-debit-max` int(5) NOT NULL, " .
                         "`limit-rate-max` int(5) NOT NULL, " .
                         "PRIMARY KEY (`profileId`, `shopId`)" .
                         ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlOrderPositions = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_positions` (" .
                                 "`s_order_details_id` int(11) NOT NULL," .
                                 "`delivered` int NOT NULL DEFAULT 0, " .
                                 "`cancelled` int NOT NULL DEFAULT 0, " .
                                 "`returned` int NOT NULL DEFAULT 0, " .
                                 "PRIMARY KEY (`s_order_details_id`)" .
                                 ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlOrderShipping = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_shipping` (" .
                                "`s_order_id` int(11) NOT NULL," .
                                "`delivered` int NOT NULL DEFAULT 0, " .
                                "`cancelled` int NOT NULL DEFAULT 0, " .
                                "`returned` int NOT NULL DEFAULT 0, " .
                                "PRIMARY KEY (`s_order_id`)" .
                                ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlOrderHistory = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_order_history` (" .
                               "`id` int(11) NOT NULL AUTO_INCREMENT," .
                               "`orderId` varchar(50) ," .
                               "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, " .
                               "`event` varchar(100), " .
                               "`articlename` varchar(100), " .
                               "`articlenumber` varchar(50), " .
                               "`quantity` varchar(50), " .
                               "PRIMARY KEY (`id`)" .
                               ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
            try {
                Shopware()->Db()->query($sqlBankdata);
                Shopware()->Db()->query($sqlLogging);
                Shopware()->Db()->query($sqlConfig);
                Shopware()->Db()->query($sqlOrderPositions);
                Shopware()->Db()->query($sqlOrderShipping);
                Shopware()->Db()->query($sqlOrderHistory);
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception('Can not create Database.' . $exception->getMessage());
            }
        }

        /**
         * Creates the Menuentry for the RatePAY-logging
         */
        private function _createMenu()
        {
            try {
                $parent = $this->Menu()->findOneBy('label', 'logfile');
                $this->createMenuItem(array(
                        'label'      => 'RatePAY',
                        'class'      => 'sprite-cards-stack',
                        'active'     => 1,
                        'controller' => 'RpayRatepayLogging',
                        'action'     => 'index',
                        'parent'     => $parent
                    )
                );
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception("Can not create menuentry." . $exception->getMessage());
            }
        }

        /**
         * Subcribe eventslistener for the events
         *
         * @throws Exception Error: Can not create events.
         */
        private function _subscribeEvents()
        {
            try {
                //Hook into backend order controller
                $this->subscribeEvent(
                    'Shopware_Controllers_Backend_Order::saveAction::before', 'beforeSaveOrderInBackend'
                );
                $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch', 110
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Dispatcher_ControllerPath_Frontend_RpayRatepay', 'frontendPaymentController'
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Dispatcher_ControllerPath_Backend_RpayRatepayLogging', 'onLoggingBackendController'
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Dispatcher_ControllerPath_Backend_RpayRatepayOrderDetail', 'onOrderDetailBackendController'
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'preValidation'
                );
                $this->subscribeEvent(
                    'Shopware_Modules_Admin_GetPaymentMeans_DataFilter', 'filterPayments'
                );
                $this->subscribeEvent(
                    'Shopware_Controllers_Backend_Config::saveFormAction::before', 'beforeSavePluginConfig'
                );
                $this->subscribeEvent(
                    'Shopware_Controllers_Backend_Order::deletePositionAction::before', 'beforeDeleteOrderPosition'
                );
                $this->subscribeEvent(
                    'Shopware_Controllers_Backend_Order::deleteAction::before', 'beforeDeleteOrder'
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Action_PostDispatch_Backend_Order', 'extendOrderDetailView'
                );
                $this->subscribeEvent(
                    'Shopware_Modules_Order_SaveOrder_ProcessDetails', 'insertRatepayPositions'
                );
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception('Can not create events.' . $exception->getMessage());
            }
        }

        /**
         * @param Enlight_Event_EventArgs $args
         */
        public function onPostDispatch(Enlight_Event_EventArgs $args)
        {
            /** @var $action Enlight_Controller_Action */
            $action = $args->getSubject();
            $request = $action->Request();
            $response = $action->Response();
            $view = $action->View();

            if (!$request->isDispatched()
                || $response->isException()
                || $request->getModuleName() != 'frontend'
                || !$view->hasTemplate()
            ) {
                return;
            }

            $session = Shopware()->Session();
            $manager = Shopware()->Models();
            $config = Shopware()->Config();

            if('checkout' === $request->getControllerName() && 'shippingPayment' === $request->getActionName())
            {
                $this->registerMyTemplateDir();
                $view->extendsTemplate('frontend/payment_rpay_part/index/header.tpl');
                $view->extendsTemplate('frontend/payment_rpay_part/checkout/change_payment.tpl');
            }

        }

        /**
         * @param bool $isBackend
         */
        protected function registerMyTemplateDir($isBackend = false)
        {
            if ($isBackend) {
                $this->Application()->Template()->addTemplateDir(__DIR__ . '/Views/', 'rpay');
            } elseif ($this->isTemplateResponsive()) {
                $this->Application()->Template()->addTemplateDir(__DIR__ . '/Views/responsive', 'rpay');
            } else {
                $this->Application()->Template()->addTemplateDir(__DIR__ . '/Views/_emotion', 'rpay');
            }
        }

        /**
         * Checks if the the current Template is responsive
         *
         * @return bool
         */
        private function isTemplateResponsive()
        {
            $template = $this->Application()->Shop()->getTemplate()->getVersion();
            if ($template < 3) {
                return false;
            }

            return true;
        }

        /**
         * Checks if the payment method is a ratepay method. If it is a ratepay method, throw an exception
         * and forbit to change the payment method
         */
        public function beforeSaveOrderInBackend(Enlight_Hook_HookArgs $arguments)
        {
            $request = $arguments->getSubject()->Request();
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $request->getParam('id'));
            $newPaymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $request->getParam('paymentId'));

            if ((!in_array($order->getPayment()->getName(), array('rpayratepayinvoice', 'rpayratepayrate', 'rpayratepaydebit')) && in_array($newPaymentMethod->getName(), array('rpayratepayinvoice', 'rpayratepayrate', 'rpayratepaydebit')))
                || (in_array($order->getPayment()->getName(), array('rpayratepayinvoice', 'rpayratepayrate', 'rpayratepaydebit')) && $newPaymentMethod->getName() != $order->getPayment()->getName())
            ) {
                Shopware()->Pluginlogger()->addNotice('RatePAY', 'Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlarten ge&auml;ndert werden.');
                $arguments->stop();
                throw new Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlarten ge&auml;ndert werden.');
            }

            return false;

        }


        /**
         * Checks if credentials are set and gets the configuration via profile_request
         *
         * @param Enlight_Hook_HookArgs $arguments
         *
         * @return null
         */
        public function beforeSavePluginConfig(Enlight_Hook_HookArgs $arguments)
        {

            $request = $arguments->getSubject()->Request();
            $parameter = $request->getParams();

            if ($parameter['name'] !== $this->getName() || $parameter['controller'] !== 'config') {
                return;
            }

            $credentials = array();

            foreach ($parameter['elements'] as $element) {

                //DE
                if ($element['name'] === 'RatePayProfileIDDE') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['de']['profileID'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySecurityCodeDE') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['de']['securityCode'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySandboxDE') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['de']['sandbox'] = $element['value'];
                    }
                }

                //AT
                if ($element['name'] === 'RatePayProfileIDAT') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['at']['profileID'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySecurityCodeAT')
                {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['at']['securityCode'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySandboxAT') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['at']['sandbox'] = $element['value'];
                    }
                }

            }

            //DE Profile Request
            foreach($credentials as $shopId => $credentials) {

                if (
                    null !== $credentials['de']['profileID']
                    &&
                    null !== $credentials['de']['securityCode']
                    &&
                    null !== $credentials['de']['sandbox']
                )
                {
                    if ($this->getRatepayConfig($credentials['de']['profileID'], $credentials['de']['securityCode'], $shopId, $credentials['de']['sandbox'])) {
                        Shopware()->PluginLogger()->addNotice('RatePAY', 'Ruleset for germany successfully updated.');
                    }
                }

                //AT Profile Request
                if (
                    null !== $credentials['at']['profileID']
                    &&
                    null !== $credentials['at']['securityCode']
                    &&
                    null !== $credentials['at']['sandbox']
                )
                {
                    if ($this->getRatepayConfig($credentials['at']['profileID'], $credentials['at']['securityCode'], $shopId, $credentials['at']['sandbox'])) {
                        Shopware()->Pluginlogger()->info('RatePAY: Ruleset for austria successfully updated.');
                    }
                }

            }


        }

        /**
         * Stops Orderdeletation, when its not permitted
         *
         * @param Enlight_Hook_HookArgs $arguments
         *
         * @return true
         */
        public function beforeDeleteOrderPosition(Enlight_Hook_HookArgs $arguments)
        {
            $request = $arguments->getSubject()->Request();
            $parameter = $request->getParams();
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['orderID']);
            if ($parameter['valid'] != true && in_array($order->getPayment()->getName(), array("rpayratepayinvoice", "rpayratepayrate", "rpayratepaydebit"))) {
                Shopware()->Pluginlogger()->warning('Positionen einer RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden. Bitte Stornieren Sie die Artikel in der Artikelverwaltung.');
                $arguments->stop();
            }

            return true;
        }

        /**
         * Stops Orderdeletation, when any article has been send
         *
         * @param Enlight_Hook_HookArgs $arguments
         */
        public function beforeDeleteOrder(Enlight_Hook_HookArgs $arguments)
        {
            $request = $arguments->getSubject()->Request();
            $parameter = $request->getParams();
            if (!in_array($parameter['payment'][0]['name'], array("rpayratepayinvoice", "rpayratepayrate", "rpayratepaydebit"))) {
                return false;
            }
            $sql = "SELECT COUNT(*) FROM `s_order_details` AS `detail` "
                   . "INNER JOIN `rpay_ratepay_order_positions` AS `position` "
                   . "ON `position`.`s_order_details_id` = `detail`.`id` "
                   . "WHERE `detail`.`orderID`=? AND "
                   . "(`position`.`delivered` > 0 OR `position`.`cancelled` > 0 OR `position`.`returned` > 0)";
            $count = Shopware()->Db()->fetchOne($sql, array($parameter['id']));
            if ($count > 0) {
                Shopware()->Pluginlogger()->warning('RatePAY-Bestellung k&ouml;nnen nicht gelöscht werden, wenn sie bereits bearbeitet worden sind.');
                $arguments->stop();
            }
            else {
                $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
                $request = new Shopware_Plugins_Frontend_RpayRatePay_Component_Service_RequestService($config->get('RatePaySandbox'));

                $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
                $modelFactory->setTransactionId($parameter['transactionId']);
                $paymentChange = $modelFactory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange());
                $head = $paymentChange->getHead();
                $head->setOperationSubstring('full-cancellation');
                $paymentChange->setHead($head);
                $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
                $basket->setAmount(0);
                $basket->setCurrency($parameter['currency']);
                $paymentChange->setShoppingBasket($basket);
                $response = $request->xmlRequest($paymentChange->toArray());
                $result = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('PAYMENT_CHANGE', $response);
                if (!$result) {
                    Shopware()->Pluginlogger()->warning('Bestellung k&ouml;nnte nicht gelöscht werden, da die Stornierung bei RatePAY fehlgeschlagen ist.');
                    $arguments->stop();
                }
            }
        }

        /**
         * Eventlistener for frontendcontroller
         *
         * @param Enlight_Event_EventArgs $arguments
         *
         * @return string
         */
        public function frontendPaymentController(Enlight_Event_EventArgs $arguments)
        {
            $this->registerMyTemplateDir();

            return $this->Path() . '/Controller/frontend/RpayRatepay.php';
        }

        /**
         * Loads the Backendextentions
         *
         * @param Enlight_Event_EventArgs $arguments
         */
        public function onLoggingBackendController()
        {
            Shopware()->Template()->addTemplateDir($this->Path() . 'Views/');

            return $this->Path() . "/Controller/backend/RpayRatepayLogging.php";
        }

        /**
         * Loads the Backendextentions
         */
        public function onOrderDetailBackendController()
        {
            Shopware()->Template()->addTemplateDir($this->Path() . 'Views/');

            return $this->Path() . "/Controller/backend/RpayRatepayOrderDetail.php";
        }

        /**
         * Saves Data into the rpay_ratepay_order_position
         *
         * @param Enlight_Event_EventArgs $arguments
         */
        public function insertRatepayPositions(Enlight_Event_EventArgs $arguments)
        {
            $ordernumber = $arguments->getSubject()->sOrderNumber;

            try {
                $isRatePAYpaymentSQL = "SELECT COUNT(*) FROM `s_order` "
                                       . "JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id`=`s_order`.`paymentID` "
                                       . "WHERE  `s_order`.`ordernumber`=? AND`s_core_paymentmeans`.`name` LIKE 'rpayratepay%';";
                $isRatePAYpayment = Shopware()->Db()->fetchOne($isRatePAYpaymentSQL, array($ordernumber));
                Shopware()->Pluginlogger()->info($isRatePAYpayment);
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
                $isRatePAYpayment = 0;
            }

            if ($isRatePAYpayment != 0) {
                $sql = "SELECT `id` FROM `s_order_details` WHERE `ordernumber`=?;";
                $rows = Shopware()->Db()->fetchAll($sql, array($ordernumber));
                $values = "";
                foreach ($rows as $row) {
                    $values .= "(" . $row['id'] . "),";
                }
                $values = substr($values, 0, -1);
                $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` "
                             . "(`s_order_details_id`) "
                             . "VALUES " . $values;
                try {
                    Shopware()->Db()->query($sqlInsert);
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                }
            }

            return $ordernumber;
        }

        /**
         * validated the Userdata
         *
         * @param Enlight_Event_EventArgs $arguments
         */
        public function preValidation(Enlight_Event_EventArgs $arguments)
        {

            $request = $arguments->getSubject()->Request();
            $response = $arguments->getSubject()->Response();
            $view = $arguments->getSubject()->View();

            if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
                return;
            }

            // Check for the right Action
            if (!in_array('confirm', array($request->get('action'), $view->sTargetAction)) || $request->get('controller') !== 'checkout') {
                return;
            }

            if (empty(Shopware()->Session()->sUserId)) {
                Shopware()->Pluginlogger()->warning('RatePAY: sUserId is empty');

                return;
            }
            Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation();

            if ($validation->isRatePAYPayment()) {
                $view->sRegisterFinished = 'false';

                $view->ratepayValidateUST = $validation->isUSTSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isUSTSet->' . $view->ratepayValidateUST);

                $view->ratepayValidateCompanyName = $validation->isCompanyNameSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isCompanyNameSet->' . $view->ratepayValidateCompanyName);

                $view->ratepayValidateIsB2B = $validation->isCompanyNameSet() || $validation->isUSTSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isB2B->' . $view->ratepayValidateIsB2B);

                $view->ratepayIsBillingAddressSameLikeShippingAddress = $validation->isBillingAddressSameLikeShippingAddress() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isBillingAddressSameLikeShippingAddress->' . $view->ratepayIsBillingAddressSameLikeShippingAddress);

                $view->ratepayValidateIsBirthdayValid = true;
                $view->ratepayValidateisAgeValid = true;

                $view->ratepayValidateisDebitSet = $validation->isDebitSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isDebitSet->' . $view->ratepayValidateisDebitSet);

                $view->ratepayErrorRatenrechner = Shopware()->Session()->ratepayErrorRatenrechner ? 'true' : 'false';
            }
        }

        /**
         * Filters the shown Payments
         * RatePAY-payments will be hidden, if one of the following requirement is not given
         *  - Delivery Address is not allowed to be not the same as billing address
         *  - The Customer must be over 18 years old
         *  - The Country must be germany or austria
         *  - The Currency must be EUR
         *
         * @param Enlight_Event_EventArgs $arguments
         *
         * @return array
         */
        public function filterPayments(Enlight_Event_EventArgs $arguments)
        {

            $return = $arguments->getReturn();
            $currency = Shopware()->Config()->get('currency');

            if (empty(Shopware()->Session()->sUserId) || empty($currency)) {
                return;
            }

            $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

            //get country of order
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId());

            //get current shopId
            $shopId = Shopware()->Shop()->getId();

            //fetch correct config for current shop based on user country
            $profileId = null;
            if('DE' === $country->getIso())
            {
                $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileIDDE');
            } elseif ('AT' === $country->getIso())
            {
                $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileIDAT');
            }

            //get ratepay config based on shopId and profileId
            $paymentStati = Shopware()->Db()->fetchRow('
                SELECT
                *
                FROM
                `rpay_ratepay_config`
                WHERE
                `shopId` =?
                AND
                `profileId`=?
            ', array($shopId, $profileId));

            $showRate    = $paymentStati['rateStatus']    == 2 ? true : false;
            $showDebit   = $paymentStati['debitStatus']   == 2 ? true : false;
            $showInvoice = $paymentStati['invoiceStatus'] == 2 ? true : false;

            //check if the country is germany or austria
            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation();
            if (!$validation->isCountryValid()) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            //check if it is a b2b transaction
            if ($validation->isCompanyNameSet() || $validation->isUSTSet()) {
                $showRate    = $paymentStati['b2b-rate']    == 'yes' && $showRate ? true : false;
                $showDebit   = $paymentStati['b2b-debit']   == 'yes' && $showDebit ? true : false;
                $showInvoice = $paymentStati['b2b-invoice'] == 'yes' && $showInvoice ? true : false;
            }

            //check if there is an alternate delivery address
            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                $showRate    = $paymentStati['address-rate']    == 'yes' && $validation->isCountryValid() && $showRate ? true : false;
                $showDebit   = $paymentStati['address-debit']   == 'yes' && $validation->isCountryValid() && $showDebit ? true : false;
                $showInvoice = $paymentStati['address-invoice'] == 'yes' && $validation->isCountryValid() && $showInvoice ? true : false;
            }

            //check if payments are hidden by session
            if (true === Shopware()->Session()->RatePAY['hidePayment']) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            //check the limits
            if (Shopware()->Modules()->Basket()) {

                $basket = Shopware()->Modules()->Basket()->sGetAmount();
                $basket = $basket['totalAmount'];

                Shopware()->Pluginlogger()->info('BasketAmount: $basket');

                if ($basket < $paymentStati['limit-invoice-min'] || $basket > $paymentStati['limit-invoice-max']) {
                    $showInvoice = false;
                }

                if ($basket < $paymentStati['limit-debit-min'] || $basket > $paymentStati['limit-debit-max']) {
                    $showDebit = false;
                }

                if ($basket < $paymentStati['limit-rate-min'] || $basket > $paymentStati['limit-rate-max']) {
                    $showRate = false;
                }

            }

            $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
            $setToDefaultPayment = false;

            $payments = array();
            foreach ($return as $payment) {
                if ($payment['name'] === 'rpayratepayinvoice' && !$showInvoice) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Invoice');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepayinvoice" ? : $setToDefaultPayment;
                    continue;
                }
                if ($payment['name'] === 'rpayratepaydebit' && !$showDebit) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Debit');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepaydebit" ? : $setToDefaultPayment;
                    continue;
                }
                if ($payment['name'] === 'rpayratepayrate' && !$showRate) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate" ? : $setToDefaultPayment;
                    continue;
                }
                $payments[] = $payment;
            }

            if ($setToDefaultPayment) {
                Shopware()->Pluginlogger()->info($user->getPaymentId());
                $user->setPaymentId(Shopware()->Config()->get('paymentdefault'));
                Shopware()->Models()->persist($user);
                Shopware()->Models()->flush();
                Shopware()->Models()->refresh($user);
                Shopware()->Pluginlogger()->info($user->getPaymentId());
            }

            return $payments;
        }

        /**
         * Sends a Profile_request and saves the data into the Database
         *
         * @param string $profileId
         * @param string $securityCode
         *
         * @return boolean
         */
        private function getRatepayConfig($profileId, $securityCode, $shopId, $sandbox)
        {
            $factory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            $profileRequestModel = $factory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ProfileRequest());
            $head = $profileRequestModel->getHead();
            $head->setProfileId($profileId);
            $head->setSecurityCode($securityCode);
            $profileRequestModel->setHead($head);
            $requestService = new Shopware_Plugins_Frontend_RpayRatePay_Component_Service_RequestService($sandbox);

            $response = $requestService->xmlRequest($profileRequestModel->toArray());

            if (Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('PROFILE_REQUEST', $response)) {
                $data = array(
                    $response->getElementsByTagName('profile-id')->item(0)->nodeValue,
                    $response->getElementsByTagName('activation-status-invoice')->item(0)->nodeValue,
                    $response->getElementsByTagName('activation-status-elv')->item(0)->nodeValue,
                    $response->getElementsByTagName('activation-status-installment')->item(0)->nodeValue,
                    $response->getElementsByTagName('b2b-invoice')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('b2b-elv')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('b2b-installment')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('delivery-address-invoice')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('delivery-address-elv')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('delivery-address-installment')->item(0)->nodeValue ? : 'no',
                    $response->getElementsByTagName('tx-limit-invoice-min')->item(0)->nodeValue,
                    $response->getElementsByTagName('tx-limit-elv-min')->item(0)->nodeValue,
                    $response->getElementsByTagName('tx-limit-installment-min')->item(0)->nodeValue,
                    $response->getElementsByTagName('tx-limit-invoice-max')->item(0)->nodeValue,
                    $response->getElementsByTagName('tx-limit-elv-max')->item(0)->nodeValue,
                    $response->getElementsByTagName('tx-limit-installment-max')->item(0)->nodeValue,
                    $shopId
                );

                $activePayments = '';
                if ($response->getElementsByTagName('activation-status-invoice')->item(0)->nodeValue == 2) {
                    $activePayments = $activePayments == '' ? '"rpayratepayinvoice"' : $activePayments . ', "rpayratepayinvoice"';
                }
                if ($response->getElementsByTagName('activation-status-elv')->item(0)->nodeValue == 2) {
                    $activePayments = $activePayments == '' ? '"rpayratepaydebit"' : $activePayments . ', "rpayratepaydebit"';
                }
                if ($response->getElementsByTagName('activation-status-installment')->item(0)->nodeValue == 2) {
                    $activePayments = $activePayments == '' ? '"rpayratepayrate"' : $activePayments . ', "rpayratepayrate"';
                }

                $updatesql = "UPDATE `s_core_paymentmeans` SET `active` = 1 WHERE `name` in($activePayments)";
                $sql = "REPLACE INTO `rpay_ratepay_config`"
                       . "(`profileId`, `invoiceStatus`,`debitStatus`,`rateStatus`, "
                       . "`b2b-invoice`, `b2b-debit`, `b2b-rate`, "
                       . "`address-invoice`, `address-debit`, `address-rate`, "
                       . "`limit-invoice-min`, `limit-debit-min`, `limit-rate-min`, "
                       . "`limit-invoice-max`, `limit-debit-max`, `limit-rate-max`, "
                       . "`shopId`)"
                       . "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

                try {
                    $this->clearRuleSet();
                    $this->setRuleSet(
                        'rpayratepayinvoice', 'CURRENCIESISOISNOT', 'EUR'
                    );
                    $this->setRuleSet(
                        'rpayratepaydebit', 'CURRENCIESISOISNOT', 'EUR'
                    );
                    $this->setRuleSet(
                        'rpayratepayrate', 'CURRENCIESISOISNOT', 'EUR'
                    );
                    Shopware()->Db()->query($sql, $data);
                    Shopware()->Db()->query($updatesql);

                    return true;
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->info($exception->getMessage());
                    Shopware()->Db()->query("UPDATE `s_core_paymentmeans` SET `active` =0 WHERE `name` LIKE 'rpayratepay%'");

                    return false;
                }
            }
            else {
                Shopware()->Pluginlogger()->error('RatePAY: Profile_Request failed!');
                Shopware()->Db()->query("UPDATE `s_core_paymentmeans` SET `active` =0 WHERE `name` LIKE 'rpayratepay%'");

                return false;
            }
        }

        /**
         * Sets the Ruleset for the given Payment
         *
         * @param string $paymentName
         * @param string $firstRule
         * @param string $firstValue
         */
        private function setRuleSet($paymentName, $firstRule, $firstValue)
        {
            $payment = $this->Payments()->findOneBy(array('name' => $paymentName));
            $ruleset = new Shopware\Models\Payment\RuleSet;
            $ruleset->setPayment($payment);
            $ruleset->setRule1($firstRule);
            $ruleset->setValue1($firstValue);
            $ruleset->setRule2('');
            $ruleset->setValue2(0);
            Shopware()->Models()->persist($ruleset);
        }

        /**
         * Clears the Ruleset for all RatePAY-Payments
         */
        private function clearRuleSet()
        {
            $sql = "DELETE FROM `s_core_rulesets` "
                   . "WHERE `paymentID` IN("
                   . "SELECT `id` FROM `s_core_paymentmeans` "
                   . "WHERE `name` LIKE 'rpayratepay%'"
                   . ") AND `rule1` LIKE 'ORDERVALUE%' OR `rule1` = 'CURRENCIESISOISNOT';";
            Shopware()->Db()->query($sql);
        }

        /**
         * extends the Orderdetailview
         *
         * @param Enlight_Event_EventArgs $arguments
         */
        public function extendOrderDetailView(Enlight_Event_EventArgs $arguments)
        {
            $arguments->getSubject()->View()->addTemplateDir(
                $this->Path() . 'Views/backend/rpay_ratepay_orderdetail/'
            );

            if ($arguments->getRequest()->getActionName() === 'load') {
                $arguments->getSubject()->View()->extendsTemplate(
                    'backend/order/view/detail/ratepaydetailorder.js'
                );
            }

            if ($arguments->getRequest()->getActionName() === 'index') {
                $arguments->getSubject()->View()->extendsTemplate(
                    'backend/order/ratepayapp.js'
                );
            }
        }

    }
