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
            $this->get('Loader')->registerNamespace('RatePAY', $this->Path() . 'Component/Core/src/');
        }

        /**
         * Returns the Pluginversion
         *
         * @return string
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
                Shopware()->Pluginlogger()->addNotice($exception->getMessage());
            }
            try {
                Shopware()->Db()->query($sql, array(
                    265, 'Teil-(Storno)', 265, 'state', 0
                ));
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->addNotice($exception->getMessage());
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
                Shopware()->Pluginlogger()->addNotice($exception->getMessage());
            }
        }

        /**
         * Updates the Plugin and its components
         *
         * @param string $version
         *
         * @todo: implement translation update while updating
         */
        public function update($version)
        {
            $this->_subscribeEvents();
            $this->_createForm();

            $this->_incrementalTableUpdate();

            $this->_dropOrderAdditionalAttributes();

            return array(
                'success' => true,
                'invalidateCache' => array(
                    'frontend',
                    'backend'
                )
            );
        }

        /**
        * drops additional attributes for ratepay orders in s_order_attributes
        */
        public function _dropOrderAdditionalAttributes()
        {
            Shopware()->Models()->removeAttribute('s_order_attributes','RatePAY','ShopId');
            Shopware()->Models()->removeAttribute('s_order_attributes','RatePAY','TransactionId');
            Shopware()->Models()->removeAttribute('s_order_attributes','RatePAY','DgNumber');

            $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
            $metaDataCache->deleteAll();
            Shopware()->Models()->generateAttributeModels(
                array('s_order_attributes')
            );
        }

        /**
         * Starts incremental update/alter queries in case of update
         *
         * @throws Exception
         */
        private function _incrementalTableUpdate() {
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "device-fingerprint-status")) {
                if ($this->_sqlCheckIfColumnExists("rpay_ratepay_config", "deviceFingerprintStatus")) {
                    // Changing names of fingerprint columns from version 4.2.0
                    try {
                        Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` CHANGE `deviceFingerprintStatus` `device-fingerprint-status` VARCHAR(3)");
                        Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` CHANGE `deviceFingerprintSnippetId` `device-fingerprint-snippet-id` VARCHAR(55)");
                    } catch (Exception $exception) {
                        throw new Exception("Can not change device-fingerprint columns in table `rpay_ratepay_config` - " . $exception->getMessage());
                    }
                } else {
                    // Adding device fingerprint columns into config table from version 4.1.0
                    try {
                        Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `device-fingerprint-status` varchar(3) NOT NULL");
                        Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `device-fingerprint-snippet-id` varchar(55) NOT NULL");
                    } catch (Exception $exception) {
                        throw new Exception("Can not add device-fingerprint columns in table `rpay_ratepay_config` - " . $exception->getMessage());
                    }
               }
            }

            // Adding currency and country columns into config table from version 4.2.0
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "currency")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `country-code-billing` varchar(30) NOT NULL");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `country-code-delivery` varchar(30) NOT NULL");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `currency` varchar(30) NOT NULL");
                } catch (Exception $exception) {
                    throw new Exception("Can not add country-code and currency columns in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }

            // Adding limit max b2b columns into config table from version 4.2.2
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "limit-invoice-max-b2b")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `limit-invoice-max-b2b` INT(5) NOT NULL AFTER `limit-rate-max`;");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `limit-debit-max-b2b` INT(5) NOT NULL AFTER `limit-invoice-max-b2b`;");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `limit-rate-max-b2b` INT(5) NOT NULL AFTER `limit-debit-max-b2b`;");
                } catch (Exception $exception) {
                    throw new Exception("Can not add max b2b columns in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }

            //Adding error-default message into config table from version 4.2.2
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "error_default")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `error-default` VARCHAR(535) NOT NULL DEFAULT 'Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href=\"http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach\" target=\"_blank\">RatePAY-Datenschutzerklärung</a>' AFTER `currency`;");
                } catch (Exception $exception) {
                    throw new Exception("Can not add error default column in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }

            //Adding error-default message into config table from version 4.2.2
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "month-allowed")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `month-allowed` VARCHAR(30) NULL ");
                    Shopware()->DB()->query("ALTER TABLE `rpay_ratepay_config` ADD `rate-min-normal` float NULL ");
                    Shopware()->DB()->query("ALTER TABLE `rpay_ratepay_config` ADD `interestrate-default` float NULL ");
                }catch (Exception $exception) {
                    throw new Exception("Can not add month-allowed, rate-min-normal and interestrate-default columns in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }

            //Adding error-default message into config table from version 4.2.2
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "payment-firstday")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `payment-firstday` VARCHAR(2) NULL ");
                } catch (Exception $exception) {
                    throw new Exception("Can not add payment-firstdate column in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }
        }

        /**
         * Checks if column exists
         *
         * @param string $table
         * @param string $column
         * @throws Exception
         * @return bool
         */
        private function _sqlCheckIfColumnExists($table, $column)
        {
            try {
                $columnExists = Shopware()->Db()->fetchRow("
                SHOW
                    COLUMNS
                FROM
                    `" . $table . "`
                LIKE
                    '" . $column . "'");
            } catch (Exception $exception) {
                throw new Exception("Can not enter table " . $table . " - " . $exception->getMessage());
            }

            return (bool) $columnExists;
        }

        /**
         * Uninstalls the Plugin and its components
         *
         * @return boolean
         */
        public function uninstall()
        {
            $this->disable();
            $this->_dropDatabaseTables();
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
                        'description'           => 'Rechnung',
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
                        'description'           => 'Ratenzahlung',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 2,
                        'additionaldescription' => 'Kauf per Ratenzahlung',
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
                        'description'           => 'Lastschrift',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 3,
                        'additionaldescription' => 'Kauf per SEPA Lastschrift',
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

                /** CH CREDENTIALS **/
                $form->setElement('button', 'button2', array(
                    'label' => '<b>Zugangsdaten für die Schweiz:</b>',
                    'value' => ''
                ));
                $form->setElement('text', 'RatePayProfileIDCH', array(
                    'label' => 'Schweiz Profile-ID',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                $form->setElement('text', 'RatePaySecurityCodeCH', array(
                    'label' => 'Schweiz Security Code',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));
                $form->setElement('checkbox', 'RatePaySandboxCH', array(
                    'label' => 'Testmodus aktivieren ( Test Gateway )',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                ));

                /** BIDIRECTIONAL ORDER SETTINGS **/
                $form->setElement('button', 'button3', array(
                    'label' => '<b>Bidirektionalität RatePAY-Bestellungen:</b>',
                    'value' => ''
                ));

                $form->setElement('checkbox', 'RatePayBidirectional', array(
                    'label' => 'Bidirektionalität aktivieren ( Automatische Operationen an RatePAY senden, wenn sich der Bestellstatus einer RatePAY-Bestellung ändert)',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                ));

                $form->setElement(
                    'select',
                    'RatePayFullDelivery',
                    array(
                        'label' => 'Status für Volllieferung',
                        'value' => 7,
                        'store' => 'base.OrderStatus',
                        'displayField' => 'description',
                        'valueField' => 'id',
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                    )
                );

                $form->setElement(
                    'select',
                    'RatePayFullCancellation',
                    array(
                        'label' => 'Status für Vollstornierung',
                        'value' => 4,
                        'store' => 'base.OrderStatus',
                        'displayField' => 'description',
                        'valueField' => 'id',
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                    )
                );


                $form->setElement(
                    'select',
                    'RatePayFullReturn',
                    array(
                        'label' => 'Status für Vollretournierung',
                        'value' => 4,
                        'store' => 'base.OrderStatus',
                        'displayField' => 'description',
                        'valueField' => 'id',
                        'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                    )
                );

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
                         "`limit-invoice-max-b2b` int(5) NOT NULL, " .
                         "`limit-debit-max-b2b` int(5) NOT NULL, " .
                         "`limit-rate-max-b2b` int(5) NOT NULL, " .
                         "`month-allowed` varchar(30) NULL, " .
                         "`rate-min-normal` float NULL, " .
                         "`payment-firstday` varchar(5) NULL, " .
                         "`interestrate-default` float NULL, " .
                         "`device-fingerprint-status` varchar(3) NOT NULL, " .
                         "`device-fingerprint-snippet-id` varchar(55) NULL, " .
                         "`country-code-billing` varchar(30) NULL, " .
                         "`country-code-delivery` varchar(30) NULL, " .
                         "`currency` varchar(30) NULL, " .
                         "`error-default` VARCHAR(535) NOT NULL DEFAULT 'Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href=\"http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach\" target=\"_blank\">RatePAY-Datenschutzerklärung</a>', " .
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
         * Drops all RatePAY database tables
         */
        private function _dropDatabaseTables() {
            try {
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_logging`");
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config`");
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_positions`");
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_shipping`");
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_order_history`");
            } catch (Exception $exception) {
                throw new Exception('Can not delete RatePAY tables - ' . $exception->getMessage());
            }
        }

        /**
         * Creates the Menuentry for the RatePAY-logging
         */
        private function _createMenu()
        {
            try {
                $parent = $this->Menu()->findOneBy(array('label' => 'logfile'));
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
                throw new Exception("Can not create menu entry." . $exception->getMessage());
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
                $this->subscribeEvent(
                    'Shopware_Controllers_Backend_Order::saveAction::after', 'onBidirectionalSendOrderOperation'
                );
                $this->subscribeEvent(
                    'Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'extendTemplates'
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
                $this->subscribeEvent(
                    'Theme_Compiler_Collect_Plugin_Javascript',
                    'addJsFiles'
                );
            } catch (Exception $exception) {
                $this->uninstall();
                throw new Exception('Can not create events.' . $exception->getMessage());
            }
        }

        /**
         * Add base javascripts
         *
         * @return \Doctrine\Common\Collections\ArrayCollection
         */
        public function addJsFiles()
        {
            $jsPath = array(
                __DIR__ . '/Views/responsive/frontend/_public/src/javascripts/jquery.ratepay_checkout.js'
            );

            return new Doctrine\Common\Collections\ArrayCollection($jsPath);
        }

        /**
         * @param Enlight_Event_EventArgs $args
         */
        public function extendTemplates(Enlight_Event_EventArgs $args)
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

            $this->registerMyTemplateDir();

            //get ratepay config based on shopId @toDo: IF DI SNIPPET ID WILL BE VARIABLE BETWEEN SUBSHOPS WE NEED TO SELECT BY SHOPID AND COUNTRY CREDENTIALS
            $shopid = Shopware()->Shop()->getId();
            $configPlugin = $this->getRatePayPluginConfig($shopid);
            $configShop = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();;

            if (!is_null(Shopware()->Session()->sUserId)) {
                $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
                $paymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
            } elseif (!is_null(Shopware()->Session()->sPaymentID)) { // PaymentId is set in case of new/guest customers
                $paymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', Shopware()->Session()->sPaymentID);
            } else {
                return;
            }

            if(
                'checkout' === $request->getControllerName() &&
                'confirm' === $request->getActionName() &&
                strstr($paymentMethod->getName(), 'rpayratepay')
            ) {
                if (method_exists($user, 'getDefaultBillingAddress')) { // From Shopware 5.2 find current address information in default billing address
                    $view->assign('ratepayPhone', $user->getDefaultBillingAddress()->getPhone());
                    $country = $user->getDefaultBillingAddress()->getCountry()->getIso();
                    $countryCode = $user->getDefaultBillingAddress()->getCountry();
                } else {
                    $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId())->getIso();
                    $countryCode = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId());
                }

                $sandbox = $configShop->get('RatePaySandbox' . $country);
                $view->assign('ratepaySandbox', $sandbox);

                $view->extendsTemplate('frontend/payment_rpay_part/index/header.tpl');
                $view->extendsTemplate('frontend/payment_rpay_part/index/index.tpl');
                $view->extendsTemplate('frontend/payment_rpay_part/checkout/confirm.tpl');

                //if no DF token is set, receive all the necessary data to set it and extend template
                if(true == $configPlugin['device-fingerprint-status'] && !Shopware()->Session()->RatePAY['dfpToken']) {
                    $view->assign('snippetId', $configPlugin['device-fingerprint-snippet-id']);

                    try {
                        $sId = Shopware()->SessionID();
                    } catch (Exception $exception) {}

                    $tokenFirstPart = (!empty($sId)) ? $sId : rand();

                    $token = md5($tokenFirstPart . microtime());
                    Shopware()->Session()->RatePAY['dfpToken'] = $token;
                    $view->assign('token', Shopware()->Session()->RatePAY['dfpToken']);

                    $view->extendsTemplate('frontend/payment_rpay_part/index/dfp.tpl');
                }
            }
        }

        /**
         * @param bool $isBackend
         */
        protected function registerMyTemplateDir($isBackend = false)
        {
            $this->Application()->Template()->addTemplateDir(__DIR__ . '/Views/responsive', 'rpay');
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
                Shopware()->Pluginlogger()->addNotice('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
                $arguments->stop();
                throw new Exception('Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf RatePay Zahlungsmethoden ge&auml;ndert werden und RatePay Bestellungen k&ouml;nnen nicht nachtr&auml;glich auf andere Zahlungsarten ge&auml;ndert werden.');
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

                //CH
                if ($element['name'] === 'RatePayProfileIDCH') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['ch']['profileID'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySecurityCodeCH')
                {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['ch']['securityCode'] = $element['value'];
                    }
                }
                if ($element['name'] === 'RatePaySandboxCH') {
                    foreach($element['values'] as $element) {
                        $credentials[$element['shopId']]['ch']['sandbox'] = $element['value'];
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
                        Shopware()->PluginLogger()->addNotice('Ruleset for Germany successfully updated.');
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
                        Shopware()->Pluginlogger()->info('RatePAY: Ruleset for Austria successfully updated.');
                    }
                }

                //CH Profile Request
                if (
                    null !== $credentials['ch']['profileID']
                    &&
                    null !== $credentials['ch']['securityCode']
                    &&
                    null !== $credentials['ch']['sandbox']
                )
                {
                    if ($this->getRatepayConfig($credentials['ch']['profileID'], $credentials['ch']['securityCode'], $shopId, $credentials['ch']['sandbox'])) {
                        Shopware()->Pluginlogger()->info('RatePAY: Ruleset for Switzerland successfully updated.');
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

        public function onBidirectionalSendOrderOperation(Enlight_Hook_HookArgs $arguments)
        {
            $request = $arguments->getSubject()->Request();
            $parameter = $request->getParams();
            $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

            if (true !== $config->get('RatePayBidirectional') || (!in_array(
                    $parameter['payment'][0]['name'],
                    array("rpayratepayinvoice", "rpayratepayrate", "rpayratepaydebit")
                ))
            ) {
                return;
            }

            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

            //get country of order
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $order->getCustomer()->getBilling()->getCountryId());

            //set sandbox mode based on config
            $sandbox = $config->get('RatePaySandbox' . $country->getIso());

            $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            $history      = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();

            $sqlShipping = "SELECT invoice_shipping FROM s_order WHERE id = ?";
            $shippingCosts = Shopware()->Db()->fetchOne($sqlShipping, array($parameter['id']));

            $items = array();
            $i = 0;
            foreach ($order->getDetails() as $item) {
                $items[$i]['articlename'] = $item->getArticlename();
                $items[$i]['ordernumber'] = $item->getArticlenumber();
                $items[$i]['quantity'] = $item->getQuantity();
                $items[$i]['priceNumeric'] = $item->getPrice();
                $items[$i]['tax_rate'] = $item->getTaxRate();
                $taxRate = $item->getTaxRate();
                $i++;
            }
            if (!empty($shippingCosts)) {
                $items['Shipping']['articlename'] = 'Shipping';
                $items['Shipping']['ordernumber'] = 'shipping';
                $items['Shipping']['quantity'] = 1;
                $items['Shipping']['priceNumeric'] = $shippingCosts;
                $items['Shipping']['tax_rate'] = $taxRate;
            }

            $newOrderStatus = $parameter['status'];

            if ($newOrderStatus == $config['RatePayFullDelivery']) {

                $sqlOrderDetailId = "SELECT id FROM s_order_details where orderId = ?";
                $orderDetailId = Shopware()->Db()->fetchOne($sqlOrderDetailId, array($order->getId()));

                $sql = "SELECT COUNT(*) "
                        . "FROM `rpay_ratepay_order_positions` AS `shipping` "
                        . "WHERE `delivered` = 0 AND `cancelled` = 0 AND `returned` = 0 AND `shipping`.`s_order_details_id` = ?";

                try {
                    $count = Shopware()->Db()->fetchOne($sql, array($orderDetailId));
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                }

                if (null != $count) {
                    $modelFactory->setSandboxMode($sandbox);
                    $modelFactory->setTransactionId($order->getTransactionID());
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $modelFactory->setOrderId($order->getId());
                    $result = $modelFactory->doOperation('ConfirmationDeliver', $operationData);

                    if ($result === true) {
                        foreach ($items as $item) {
                            $bind = array(
                                'delivered' => $item['quantity']
                            );

                            $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                            if ($item['quantity'] <= 0) {
                                continue;
                            }
                            $history->logHistory($order->getId(), "Artikel wurde versand.", $item['articlename'], $item['ordernumber'], $item['quantity']);
                        }
                    }

                }

            }
            if ($newOrderStatus == $config['RatePayFullCancellation']) {
                $sqlOrderDetailId = "SELECT id FROM s_order_details where orderId = ?";
                $orderDetailId = Shopware()->Db()->fetchOne($sqlOrderDetailId, array($order->getId()));

                $sql = "SELECT COUNT(*) "
                        . "FROM `rpay_ratepay_order_positions` AS `shipping` "
                        . "WHERE `cancelled` = 0 AND `delivered` = 0 AND `shipping`.`s_order_details_id` = ?";

                try {
                    $count = Shopware()->Db()->fetchOne($sql, array($orderDetailId));
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                }

                if (null != $count) {
                    $modelFactory->setSandboxMode($sandbox);
                    $modelFactory->setTransactionId($order->getTransactionID());
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $operationData['subtype'] = 'cancellation';
                    $modelFactory->setOrderId($order->getId());
                    $result = $modelFactory->doOperation('PaymentChange', $operationData);

                    if ($result === true) {
                        foreach ($items as $item) {
                            $bind = array(
                                'cancelled' => $item['quantity']
                            );
                            $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                            if ($item['quantity'] <= 0) {
                                continue;
                            }
                            $history->logHistory($order->getId(), "Artikel wurde storniert.", $item['articlename'], $item['ordernumber'], $item['quantity']);
                        }
                    }
                }
            }
            if ($newOrderStatus == $config['RatePayFullReturn']) {
                $sqlOrderDetailId = "SELECT id FROM s_order_details where orderId = ?";
                $orderDetailId = Shopware()->Db()->fetchOne($sqlOrderDetailId, array($order->getId()));

                $sql = "SELECT COUNT(*) "
                        . "FROM `rpay_ratepay_order_positions` AS `shipping` "
                        . "WHERE `returned` = 0 AND `delivered` > 0 AND `shipping`.`s_order_details_id` = ?";

                try {
                    $count = Shopware()->Db()->fetchOne($sql, array($orderDetailId));
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                }

                if (null != $count) {
                    $modelFactory->setTransactionId($order->getTransactionID());
                    $modelFactory->setSandboxMode($sandbox);
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $operationData['subtype'] = 'return';
                    $modelFactory->setOrderId($order->getId());
                    $result = $modelFactory->doOperation('PaymentChange', $operationData);

                    if ($result === true) {
                        foreach ($items as $item) {
                            $bind = array(
                                'returned' => $item['quantity']
                            );
                            $this->updateItem($order->getId(), $item['ordernumber'], $bind);
                            if ($item['quantity'] <= 0) {
                                continue;
                            }
                            $history->logHistory($order->getId(), "Artikel wurde retourniert.", $item['articlename'], $item['ordernumber'], $item['quantity']);
                        }
                    }
                }

            }

        }

        /**
         * Updates the given binding for the given article
         *
         * @param string $orderID
         * @param array  $bind
         */
        private function updateItem($orderID, $articleordernumber, $bind)
        {

            if ($articleordernumber === 'shipping') {
                Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
            } else {
                $positionId = Shopware()->Db()->fetchOne("SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?", array($orderID, $articleordernumber));
                Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $positionId);
            }
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
                $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

                //get country of order
                $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $order->getCustomer()->getBilling()->getCountryId());

                //set sandbox mode based on config
                $sandbox = $config->get('RatePaySandbox' . $country->getIso());

                $sqlShipping = "SELECT invoice_shipping FROM s_order WHERE id = ?";
                $shippingCosts = Shopware()->Db()->fetchOne($sqlShipping, array($parameter['id']));

                $items = array();
                $i = 0;
                foreach ($order->getDetails() as $item) {
                    $items[$i]['articlename'] = $item->getArticlename();
                    $items[$i]['ordernumber'] = $item->getArticlenumber();
                    $items[$i]['quantity'] = $item->getQuantity();
                    $items[$i]['priceNumeric'] = $item->getPrice();
                    $items[$i]['tax_rate'] = $item->getTaxRate();
                    $taxRate = $item->getTaxRate();
                    $i++;
                }
                if (!empty($shippingCosts)) {
                    $items['Shipping']['articlename'] = 'Shipping';
                    $items['Shipping']['ordernumber'] = 'shipping';
                    $items['Shipping']['quantity'] = 1;
                    $items['Shipping']['priceNumeric'] = $shippingCosts;
                    $items['Shipping']['tax_rate'] = $taxRate;
                }

                $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
                $modelFactory->setTransactionId($parameter['transactionId']);
                $modelFactory->setSandboxMode($sandbox);
                $modelFactory->setTransactionId($order->getTransactionID());
                $operationData['orderId'] = $order->getId();
                $operationData['items'] = $items;
                $operationData['subtype'] = 'cancellation';
                $result = $modelFactory->doOperation('PaymentChange', $operationData);

                if ($result !== true) {
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
            $request  = $arguments->getSubject()->Request();
            $response = $arguments->getSubject()->Response();
            $view     = $arguments->getSubject()->View();

            if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
                return;
            }

            // Check for the right action and controller
            if ($request->getControllerName() !== 'checkout' || $request->getActionName() !== 'confirm') {
                return;
            }

            $userId = Shopware()->Session()->sUserId;
            if (empty($userId)) {
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

                $view->errorRatenrechner = (!Shopware()->Session()->RatePAY['errorRatenrechner']) ? 'false' : Shopware()->Session()->RatePAY['errorRatenrechner'];

            }

        }

        /**
         * Get ratepay plugin config from rpay_ratepay_config table
         * 
         * @param $shopId
         * @return array
         */
        private function getRatePayPluginConfig($shopId) {
            //get ratepay config based on shopId
            return Shopware()->Db()->fetchRow('
                SELECT
                *
                FROM
                `rpay_ratepay_config`
                WHERE
                `shopId` =?
            ', array($shopId));
        }

        /**
         * Get ratepay plugin config from rpay_ratepay_config table
         *
         * @param $shopId
         * @param $country
         * @return array
         */
        private function getRatePayPluginConfigByCountry($shopId, $country) {
            //fetch correct config for current shop based on user country
            $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $country->getIso());

            //get ratepay config based on shopId and profileId
            return Shopware()->Db()->fetchRow('
                SELECT
                *
                FROM
                `rpay_ratepay_config`
                WHERE
                `shopId` =?
                AND
                `profileId`=?
            ', array($shopId, $profileId));
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
            $userId = Shopware()->Session()->sUserId;

            if (empty($userId) || empty($currency)) {
                return;
            }

            $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

            //get country of order
            if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 find current address information in default billing address
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $customerAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutBillingAddressId));
                $countryBilling = $customerAddressBilling->getCountry();
                if (Shopware()->Session()->checkoutShippingAddressId > 0 && Shopware()->Session()->checkoutShippingAddressId != Shopware()->Session()->checkoutBillingAddressId) {
                    $customerAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutShippingAddressId));
                    $countryDelivery = $customerAddressShipping->getCountry();
                } else {
                    $countryDelivery = $countryBilling;
                }
            } else {
                $countryBilling = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId());
                if (!is_null($user->getShipping()) &&$user->getBilling()->getCountryId() != $user->getShipping()->getCountryId()) {
                    $countryDelivery = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getShipping()->getCountryId());
                } else {
                    $countryDelivery = $countryBilling;
                }
            }

            //get current shopId
            $shopId = Shopware()->Shop()->getId();
            
            $config = $this->getRatePayPluginConfigByCountry($shopId, $countryBilling);

            $showRate    = $config['rateStatus']    == 2 ? true : false;
            $showDebit   = $config['debitStatus']   == 2 ? true : false;
            $showInvoice = $config['invoiceStatus'] == 2 ? true : false;

            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($config);
            $validation->setAllowedCurrencies($config['currency']);
            $validation->setAllowedCountriesBilling($config['country-code-billing']);
            $validation->setAllowedCountriesDelivery($config['country-code-delivery']);

            //check if payments are hidden by session (not in sandbox mode)
            if ($validation->isRatepayHidden()) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            //check if the country is allowed
            if (!$validation->isCurrencyValid($currency)) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            //check if the billing country is allowed
            if (!$validation->isBillingCountryValid($countryBilling)) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            //check if the delivery country is allowed
            if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                $showRate    = false;
                $showDebit   = false;
                $showInvoice = false;
            }

            $limitInvoiceMin = $config['limit-invoice-min'];
            $limitDebitMin   = $config['limit-debit-min'];
            $limitRateMin    = $config['limit-rate-min'];

            //check if it is a b2b transaction
            if ($validation->isCompanyNameSet() || $validation->isUSTSet()) {
                $showRate    = $config['b2b-rate']    == 'yes' && $showRate ? true : false;
                $showDebit   = $config['b2b-debit']   == 'yes' && $showDebit ? true : false;
                $showInvoice = $config['b2b-invoice'] == 'yes' && $showInvoice ? true : false;

                $limitInvoiceMax = ($config['limit-invoice-max-b2b'] > 0) ? $config['limit-invoice-max-b2b'] : $config['limit-invoice-max'];
                $limitDebitMax   = ($config['limit-debit-max-b2b'] > 0) ? $config['limit-debit-max-b2b'] : $config['limit-debit-max'];
                $limitRateMax    = ($config['limit-rate-max-b2b'] > 0) ? $config['limit-rate-max-b2b'] : $config['limit-rate-max'];
            } else {
                $limitInvoiceMax = $config['limit-invoice-max'];
                $limitDebitMax   = $config['limit-debit-max'];
                $limitRateMax    = $config['limit-rate-max'];
            }

            //check if there is an alternate delivery address
            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                $showRate    = $config['address-rate']    == 'yes' && $showRate ? true : false;
                $showDebit   = $config['address-debit']   == 'yes' && $showDebit ? true : false;
                $showInvoice = $config['address-invoice'] == 'yes' && $showInvoice ? true : false;
            }

            //check the limits
            if (Shopware()->Modules()->Basket()) {
                $basket = Shopware()->Modules()->Basket()->sGetAmount();
                $basket = $basket['totalAmount'];

                Shopware()->Pluginlogger()->info('BasketAmount: '.$basket);

                if ($basket < $limitInvoiceMin || $basket > $limitInvoiceMax) {
                    $showInvoice = false;
                }

                if ($basket < $limitDebitMin || $basket > $limitDebitMax) {
                    $showDebit = false;
                }

                if ($basket < $limitRateMin || $basket > $limitRateMax) {
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
            $data = array(
                'profileId' => $profileId,
                'securityCode' => $securityCode,
                'sandbox' => $sandbox);
            $response = $factory->doOperation('ProfileRequest', $data);

            if (is_array($response) && $response !== false) {
                $data = array(
                    $response['merchantConfig']['profile-id'],
                    $response['merchantConfig']['activation-status-invoice'],
                    $response['merchantConfig']['activation-status-elv'],
                    $response['merchantConfig']['activation-status-installment'],
                    $response['merchantConfig']['b2b-invoice'] ? : 'no',
                    $response['merchantConfig']['b2b-elv'] ? : 'no',
                    $response['merchantConfig']['b2b-installment'] ? : 'no',
                    $response['merchantConfig']['delivery-address-invoice'] ? : 'no',
                    $response['merchantConfig']['delivery-address-elv'] ? : 'no',
                    $response['merchantConfig']['delivery-address-installment'] ? : 'no',
                    $response['merchantConfig']['tx-limit-invoice-min'],
                    $response['merchantConfig']['tx-limit-elv-min'],
                    $response['merchantConfig']['tx-limit-installment-min'],
                    $response['merchantConfig']['tx-limit-invoice-max'],
                    $response['merchantConfig']['tx-limit-elv-max'],
                    $response['merchantConfig']['tx-limit-installment-max'],
                    $response['merchantConfig']['tx-limit-invoice-max-b2b'],
                    $response['merchantConfig']['tx-limit-elv-max-b2b'],
                    $response['merchantConfig']['tx-limit-installment-max-b2b'],
                    $response['installmentConfig']['month-allowed'],
                    $response['installmentConfig']['valid-payment-firstdays'],
                    $response['installmentConfig']['rate-min-normal'],
                    $response['installmentConfig']['interestrate-default'],
                    $response['merchantConfig']['eligibility-device-fingerprint'] ? : 'no',
                    $response['merchantConfig']['device-fingerprint-snippet-id'],
                    strtoupper($response['merchantConfig']['country-code-billing']),
                    strtoupper($response['merchantConfig']['country-code-delivery']),
                    strtoupper($response['merchantConfig']['currency']),

                    //shopId always needs be the last line
                    $shopId
                );

                $activePayments = [];
                if ($response['merchantConfig']['activation-status-invoice'] == 2) {
                    $activePayments[] = '"rpayratepayinvoice"';
                } else {
                    $inactivePayments[] = '"rpayratepayinvoice"';
                }
                if ($response['merchantConfig']['activation-status-elv'] == 2) {
                    $activePayments[] = '"rpayratepaydebit"';
                } else {
                    $inactivePayments[] = '"rpayratepaydebit"';
                }
                if ($response['merchantConfig']['activation-status-installment'] == 2) {
                    $activePayments[] = '"rpayratepayrate"';
                } else {
                    $inactivePayments[] = '"rpayratepayrate"';
                }

                if (count($activePayments) > 0) {
                    $updateSqlActivePaymentMethods = 'UPDATE `s_core_paymentmeans` SET `active` = 1 WHERE `name` in(' . implode(",", $activePayments) . ') AND `active` <> 0';
                }
                if (count($inactivePayments) > 0) {
                    $updateSqlInactivePaymentMethods = 'UPDATE `s_core_paymentmeans` SET `active` = 0 WHERE `name` in(' . implode(",", $inactivePayments) . ')';
                }

                $configSql = 'REPLACE INTO `rpay_ratepay_config`'
                       . '(`profileId`, `invoiceStatus`,`debitStatus`,`rateStatus`,'
                       . '`b2b-invoice`, `b2b-debit`, `b2b-rate`,'
                       . '`address-invoice`, `address-debit`, `address-rate`,'
                       . '`limit-invoice-min`, `limit-debit-min`, `limit-rate-min`,'
                       . '`limit-invoice-max`, `limit-debit-max`, `limit-rate-max`,'
                       . '`limit-invoice-max-b2b`, `limit-debit-max-b2b`, `limit-rate-max-b2b`,'
                       . '`month-allowed`, `payment-firstday`, `rate-min-normal`, `interestrate-default`,'
                       . '`device-fingerprint-status`, `device-fingerprint-snippet-id`,'
                       . '`country-code-billing`, `country-code-delivery`,'
                       . '`currency`,'
                       . ' `shopId`)'
                       . 'VALUES(' . substr(str_repeat('?,', 29), 0, -1) . ');'; // In case of altering cols change 29 by amount of affected cols
                try {
                    Shopware()->Db()->query($configSql, $data);
                    if (count($activePayments) > 0) {
                        Shopware()->Db()->query($updateSqlActivePaymentMethods);
                    }
                    if (count($inactivePayments) > 0) {
                        Shopware()->Db()->query($updateSqlInactivePaymentMethods);
                    }

                    return true;
                } catch (Exception $exception) {
                    Shopware()->Pluginlogger()->info($exception->getMessage());

                    return false;
                }
            }
            else {
                Shopware()->Pluginlogger()->error('RatePAY: Profile_Request failed!');
                throw new Exception('RatePAY: Profile_Request failed!');
            }
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
