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
        protected $_countries = array('de', 'at', 'ch', 'nl', 'be');

        protected $_paymentMethodes = array('rpayratepayinvoice', 'rpayratepayrate', 'rpayratepaydebit', 'rpayratepayrate0');


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
            $this->get('Loader')->registerNamespace('RatePAY', $this->Path() . 'Component/Library/src/');
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
         * Returns the PaymentConfirm Config
         *
         * @return string
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
            $this->_languageUpdate();
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
            $countries = $this->_countries;

            $configShop = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
            $this->_subscribeEvents();
            $this->_createForm();


            $this->_incrementalTableUpdate();

            $this->_languageUpdate();

            $this->_dropOrderAdditionalAttributes();

            $this->_updatePaymentMethods();

            foreach ($countries AS $country) {
                $profile = $configShop->get('RatePayProfileID' . strtoupper($country));
                $security = $configShop->get('RatePaySecurityCode' . strtoupper($country));

                if (!empty($profile)) {
                    $shops[$country]['profile'] = $profile;
                    $shops[$country]['security'] = $security;
                    $shops[$country]['id'] = Shopware()->Db()->query("SELECT `shop_id` FROM `s_core_config_values` WHERE `value` LIKE '%" . $profile . "%'");
                }
            }

            $this->_truncateConfigTable();
            $this->_incrementalTableUpdate();

            if (!empty($shops)) {
                foreach ($shops AS $county => $shop) {
                    foreach ($shop['id'] as $item) {
                        $this->getRatepayConfig($shop['profile'], $shop['security'], $item['shop_id'], $country);
                        if ($country == 'de') {
                            $this->getRatepayConfig($shop['profile'] . '_0RT', $shop['security'], $item['shop_id'], $country);
                        }
                    }
                }
            }

            Shopware()->PluginLogger()->addNotice('Successful module update');

            return array(
                'success' => true,
                'invalidateCache' => array(
                    'frontend',
                    'backend'
                )
            );
        }

        protected function _updatePaymentMethods() {
            $this->createPayment(
                array(
                    'name'                  => 'rpayratepayrate0',
                    'description'           => '0% Finanzierung',
                    'action'                => 'rpay_ratepay',
                    'active'                => 1,
                    'position'              => 4,
                    'additionaldescription' => 'Kauf per 0% Finanzierung',
                    'template'              => 'RatePAYRate.tpl',
                    'pluginID'              => $this->getId(),
                    /*'countries'             => array(
                        $this->getCountry('DE'),
                        $this->getCountry('AT')
                    )*/
                )
            );
        }

        /**
         * Update Languages for EN, FR and NL
         */
        private function _languageUpdate() {
            $locales = array(2, 108, 176);
            $german = Shopware()->Db()->query("SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = 1");

            foreach ($german AS $de) {
                foreach ($locales AS $locale) {
                    $lang = Shopware()->Db()->fetchRow("SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = " . $locale . " AND `name` = '" . $de['name'] . "'");

                    if (empty($lang)) {
                        $translation = $this->_getTranslation($locale, $de['name']);
                        if (!empty($translation)) {
                            Shopware()->Db()->insert('s_core_snippets', array(
                                'namespace' => 'RatePay',
                                'localeID' => $locale,
                                'shopID' => 1,
                                'name' => $de['name'],
                                'value' => $translation,
                            ));
                        }
                    }
                }
            }
        }

        /**
         * Truncate config table
         *
         * @return bool
         */
        private function _truncateConfigTable()
        {
            $configSql = 'TRUNCATE TABLE `rpay_ratepay_config`;';
            $configPaymentSql = 'TRUNCATE TABLE `rpay_ratepay_config_payment`;';
            $configInstallmentSql = 'TRUNCATE TABLE `rpay_ratepay_config_installment`;';
            try {
                Shopware()->Db()->query($configSql);
                Shopware()->Db()->query($configPaymentSql);
                Shopware()->Db()->query($configInstallmentSql);
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->info($exception->getMessage());
                return false;
            }
            return true;
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
            //Adding error-default message into config table from version 4.2.2
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "error_default")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `error-default` VARCHAR(535) NOT NULL DEFAULT 'Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href=\"http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach\" target=\"_blank\">RatePAY-Datenschutzerklärung</a>' AFTER `currency`;");
                } catch (Exception $exception) {
                    throw new Exception("Can not add error default column in table `rpay_ratepay_config` - " . $exception->getMessage());
                }
            }
            //adding primary index for rp config
            if ($this->_sqlCheckIfColumnIsPrimary('rpay_ratepay_config', 'profileId')) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `country` VARCHAR(30) NOT NULL AFTER `currency`;");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` DROP PRIMARY KEY;");
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD PRIMARY KEY( `shopId`, `country`);");
                } catch (Exception $exception) {
                    throw new Exception("Can not change column index` - " . $exception->getMessage());
                }
            }
            //adding sandbox index for rp config
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "sandbox")) {
                try {
                    Shopware()->Db()->query("ALTER TABLE `rpay_ratepay_config` ADD `sandbox` int(1) NOT NULL AFTER `error-default`;");
                } catch (Exception $exception) {
                    throw new Exception("Can not add sandbox column in table rpay_ratepay_config` - " . $exception->getMessage());
                }
            }

            //change config fields for module update 5.0.4
            if (!$this->_sqlCheckIfColumnExists("rpay_ratepay_config", "invoice")) {
                try {

                    $sqlAdd = "ALTER TABLE `rpay_ratepay_config` 
                                   ADD `invoice` INT(2) NOT NULL AFTER `profileId`, 
                                   ADD `debit` INT(2) NOT NULL AFTER `invoice`, 
                                   ADD `installment` INT(2) NOT NULL AFTER `debit`, 
                                   ADD `installment0` INT(2) NOT NULL AFTER `installment`, 
                                   ADD `installmentDebit` INT(2) NOT NULL AFTER `installment0`;";

                    $sqlRemove = "ALTER TABLE `rpay_ratepay_config`
                                      DROP `invoiceStatus`,
                                      DROP `debitStatus`,
                                      DROP `rateStatus`,
                                      DROP `b2b-invoice`,
                                      DROP `b2b-debit`,
                                      DROP `b2b-rate`,
                                      DROP `address-invoice`,
                                      DROP `address-debit`,
                                      DROP `address-rate`,
                                      DROP `limit-invoice-min`,
                                      DROP `limit-debit-min`,
                                      DROP `limit-rate-min`,
                                      DROP `limit-invoice-max`,
                                      DROP `limit-debit-max`,
                                      DROP `limit-rate-max`,
                                      DROP `limit-invoice-max-b2b`,
                                      DROP `limit-debit-max-b2b`,
                                      DROP `limit-rate-max-b2b`,
                                      DROP `month-allowed`,
                                      DROP `rate-min-normal`,
                                      DROP `payment-firstday`,
                                      DROP `interestrate-default`;";

                    $sqlPayment = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_payment` (" .
                                    "`rpay_id` int(2) NOT NULL AUTO_INCREMENT," .
                                    "`status` varchar(255) NOT NULL," .
                                    "`b2b` int(2) NOT NULL," .
                                    "`limit_min` int(10) NOT NULL," .
                                    "`limit_max` int(10) NOT NULL," .
                                    "`limit_max_b2b` int(10)," .
                                    "`address` int(2)," .
                                    "PRIMARY KEY (`rpay_id`)" .
                                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

                    $sqlInstallment = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_installment` (" .
                                        "`rpay_id` int(2) NOT NULL," .
                                        "`month-allowed` varchar(255) NOT NULL," .
                                        "`payment-firstday` varchar(10) NOT NULL," .
                                        "`interestrate-default` float NOT NULL," .
                                        "`rate-min-normal` float NOT NULL," .
                                        "PRIMARY KEY (`rpay_id`)" .
                                        ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

                    Shopware()->Db()->query($sqlAdd);
                    Shopware()->Db()->query($sqlRemove);
                    Shopware()->Db()->query($sqlPayment);
                    Shopware()->Db()->query($sqlInstallment);

                } catch (Exception $exception) {
                    throw new Exception("Can not change structure in rpay_ratepay_config` - " . $exception->getMessage());
                }

                //remove sandbox fields
                if ($this->_sqlCheckIfColumnExists('s_core_config_elements', 'RatePaySandboxDE')) {
                    try {
                        $sql = "DELETE FROM `s_core_config_elements` WHERE `s_core_config_elements`.`name` LIKE 'RatePaySandbox%'";
                        Shopware()->Db()->query($sql);
                    } catch (Exception $exception) {
                        throw new Exception("Can't remove Sandbox fields` - " . $exception->getMessage());
                    }
                }
            }
        }

        /**
         * check if the column index is unique
         *
         * @param $table
         * @param $column
         * @return bool
         * @throws Exception
         */
        private function _sqlCHeckIfColumnIsPrimary($table, $column) {
            try {
                $qry = 'SHOW INDEX FROM `' . $table . '` WHERE Column_name = "' . $column . '"';
                $column = Shopware()->Db()->fetchRow($qry);
            } catch (Exception $exception) {
                throw new Exception("Can not enter table " . $table . " - " . $exception->getMessage());
            }

            if ($column['Key_name'] == 'PRIMARY')  {
                return true;
            }
            return false;
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
                $this->createPayment(
                    array(
                        'name'                  => 'rpayratepayrate0',
                        'description'           => '0% Finanzierung',
                        'action'                => 'rpay_ratepay',
                        'active'                => 1,
                        'position'              => 4,
                        'additionaldescription' => 'Kauf per 0% Finanzierung',
                        'template'              => 'RatePAYRate.tpl',
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

                /** BE CREDENTIALS **/
                $form->setElement('button', 'button4', array(
                    'label' => '<b>Zugangsdaten für Belgien:</b>',
                    'value' => ''
                ));
                $form->setElement('text', 'RatePayProfileIDBE', array(
                    'label' => 'Belgien Profile-ID',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                $form->setElement('text', 'RatePaySecurityCodeBE', array(
                    'label' => 'Belgien Security Code',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                /** NL CREDENTIALS **/
                $form->setElement('button', 'button5', array(
                    'label' => '<b>Zugangsdaten für die Niederlande:</b>',
                    'value' => ''
                ));
                $form->setElement('text', 'RatePayProfileIDNL', array(
                    'label' => 'Niederlande Profile-ID',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
                ));

                $form->setElement('text', 'RatePaySecurityCodeNL', array(
                    'label' => 'Niederlande Security Code',
                    'value' => '',
                    'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
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
                        'RatePayProfileIDNL'    => 'Niederlande Profile-ID',
                        'RatePaySecurityCodeNL' => 'Niederlande Security Code',
                        'RatePayProfileIDBE'    => 'Belgien Profile-ID',
                        'RatePaySecurityCodeBE' => 'Belgien Security Code',
                        'RatePayProfileIDCH'    => 'Schweitz Profile-ID',
                        'RatePaySecurityCodeCH' => 'Schweitz Security Code',
                        'button0'               => 'Zugangsdaten für Deutschland',
                        'button1'               => 'Zugangsdaten für Österreich',
                        'button2'               => 'Zugangsdaten für die Schweiz',
                        'button4'               => 'Zugangsdaten für Belgien',
                        'button5'               => 'Zugangsdaten für die Niederlande',
                    ),
                    'en_EN' => array(
                        'RatePayProfileIDDE'    => 'Profile-ID for Germany',
                        'RatePaySecurityCodeDE' => 'Security Code for Germany',
                        'RatePayProfileIDAT'    => 'Profile-ID for Austria',
                        'RatePaySecurityCodeAT' => 'Security Code for Austria',
                        'RatePayProfileIDNL'    => 'Profile-ID for the Netherlands',
                        'RatePaySecurityCodeNL' => 'Security Code for the Netherlands',
                        'RatePayProfileIDBE'    => 'Profile-ID for Belgium',
                        'RatePaySecurityCodeBE' => 'Security Code for Belgium',
                        'RatePayProfileIDCH'    => 'Profile-ID for Switzerland',
                        'RatePaySecurityCodeCH' => 'Security Code for Switzerland',
                        'button0'               => 'Credentials for Germany',
                        'button1'               => 'Credentials for Austria',
                        'button2'               => 'Credentials for Switzerland',
                        'button4'               => 'Credentials for Belgium',
                        'button5'               => 'Credentials for the Netherlands',
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
                         "`invoice` int(2) NOT NULL, " .
                         "`debit` int(2) NOT NULL, " .
                         "`installment` int(2) NOT NULL, " .
                         "`installment0` int(2) NOT NULL, " .
                         "`installmentDebit` int(2) NOT NULL, " .
                         "`device-fingerprint-status` varchar(3) NOT NULL, " .
                         "`device-fingerprint-snippet-id` varchar(55) NULL, " .
                         "`country-code-billing` varchar(30) NULL, " .
                         "`country-code-delivery` varchar(30) NULL, " .
                         "`currency` varchar(30) NULL, " .
                         "`country` varchar(30) NOT NULL, " .
                         "`error-default` VARCHAR(535) NOT NULL DEFAULT 'Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href=\"http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach\" target=\"_blank\">RatePAY-Datenschutzerklärung</a>', " .
                         "`sandbox` int(1) NOT NULL, " .
                         "PRIMARY KEY (`shopId`, `country`)" .
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

            $sqlPayment = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_payment` (" .
                            "`rpay_id` int(2) NOT NULL AUTO_INCREMENT," .
                            "`status` varchar(255) NOT NULL," .
                            "`b2b` int(2) NOT NULL," .
                            "`limit_min` int NOT NULL," .
                            "`limit_max` int NOT NULL," .
                            "`limit_max_b2b` int," .
                            "`address` int(2) NOT NULL," .
                            "PRIMARY KEY (`rpay_id`)" .
                            ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $sqlInstallment = "CREATE TABLE IF NOT EXISTS `rpay_ratepay_config_installment` (" .
                                "`rpay_id` int(2) NOT NULL," .
                                "`month-allowed` varchar(255) NOT NULL," .
                                "`payment-firstday` varchar(10) NOT NULL," .
                                "`interestrate-default` float NOT NULL," .
                                "`rate-min-normal` float NOT NULL," .
                                "PRIMARY KEY (`rpay_id`)" .
                                ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            try {
                Shopware()->Db()->query($sqlLogging);
                Shopware()->Db()->query($sqlConfig);
                Shopware()->Db()->query($sqlOrderPositions);
                Shopware()->Db()->query($sqlOrderShipping);
                Shopware()->Db()->query($sqlOrderHistory);
                Shopware()->Db()->query($sqlPayment);
                Shopware()->Db()->query($sqlInstallment);
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
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_installment`");
                Shopware()->Db()->query("DROP TABLE IF EXISTS `rpay_ratepay_config_payment`");
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

                $sandbox = true;
                if ($configPlugin['sandbox'] == 0) {
                    $sandbox = false;
                }
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

            if ((!in_array($order->getPayment()->getName(), $this->_paymentMethodes) && in_array($newPaymentMethod->getName(), $this->_paymentMethodes))
                || (in_array($order->getPayment()->getName(), $this->_paymentMethodes) && $newPaymentMethod->getName() != $order->getPayment()->getName())
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

            //Remove old configs
            $this->_truncateConfigTable();
            $countries = $this->_countries;

            foreach ($parameter['elements'] as $element) {
                foreach ($countries AS $country) {
                    if ($element['name'] === 'RatePayProfileID' . strtoupper($country)) {
                        foreach($element['values'] as $element) {
                            $credentials[$element['shopId']][$country]['profileID'] = $element['value'];
                        }
                    }
                    if ($element['name'] === 'RatePaySecurityCode'  . strtoupper($country)) {
                        foreach($element['values'] as $element) {
                            $credentials[$element['shopId']][$country]['securityCode'] = $element['value'];
                        }
                    }
                }
            }

            foreach($credentials as $shopId => $credentials) {
                foreach ($countries AS $country) {
                    if (null !== $credentials[$country]['profileID']
                        && null !== $credentials[$country]['securityCode']
                    )
                    {
                        if ($this->getRatepayConfig($credentials[$country]['profileID'], $credentials[$country]['securityCode'], $shopId, $country)) {
                            Shopware()->PluginLogger()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
                        }
                        if ($country == 'de') {
                            if ($this->getRatepayConfig($credentials[$country]['profileID'] . '_0RT', $credentials[$country]['securityCode'], $shopId, $country)) {
                                Shopware()->PluginLogger()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
                            }
                        }
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
            if ($parameter['valid'] != true && in_array($order->getPayment()->getName(), $this->_paymentMethodes)) {
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
                    $parameter['payment'][0]['name'], $this->_paymentMethodes))
            ) {
                return;
            }

            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $parameter['id']);

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
                    $modelFactory->setTransactionId($order->getTransactionID());
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $modelFactory->setOrderId($order->getNumber());
                    $result = $modelFactory->callRequest('ConfirmationDeliver', $operationData);

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
                    $modelFactory->setTransactionId($order->getTransactionID());
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $operationData['subtype'] = 'cancellation';
                    $modelFactory->setOrderId($order->getNumber());
                    $result = $modelFactory->callRequest('PaymentChange', $operationData);

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
                    $operationData['orderId'] = $order->getId();
                    $operationData['items'] = $items;
                    $operationData['subtype'] = 'return';
                    $modelFactory->setOrderId($order->getNumber());
                    $result = $modelFactory->callRequest('PaymentChange', $operationData);

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
            if (!in_array($parameter['payment'][0]['name'], $this->_paymentMethodes)) {
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
                $modelFactory->setTransactionId($order->getTransactionID());
                $operationData['orderId'] = $order->getId();
                $operationData['items'] = $items;
                $operationData['subtype'] = 'cancellation';
                $result = $modelFactory->callRequest('PaymentChange', $operationData);

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

                $view->ratepayValidateCompanyName = $validation->isCompanyNameSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isCompanyNameSet->' . $view->ratepayValidateCompanyName);

                $view->ratepayValidateIsB2B = $validation->isCompanyNameSet() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isB2B->' . $view->ratepayValidateIsB2B);

                $view->ratepayIsBillingAddressSameLikeShippingAddress = $validation->isBillingAddressSameLikeShippingAddress() ? 'true' : 'false';
                Shopware()->Pluginlogger()->info('RatePAY: isBillingAddressSameLikeShippingAddress->' . $view->ratepayIsBillingAddressSameLikeShippingAddress);

                $view->ratepayValidateIsBirthdayValid = $validation->isBirthdayValid();
                $view->ratepayValidateisAgeValid = $validation->isAgeValid();

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
            $payments = array("installment", "invoice", "debit", "installment0");
            $paymentConfig = array();

            foreach ($payments AS $payment) {
                $qry = "SELECT * 
                        FROM `rpay_ratepay_config` AS rrc
                          JOIN `rpay_ratepay_config_payment` AS rrcp
                            ON rrcp.`rpay_id` = rrc.`" . $payment . "`
                          LEFT JOIN `rpay_ratepay_config_installment` AS rrci
                            ON rrci.`rpay_id` = rrc.`" . $payment . "`
                        WHERE rrc.`shopId` = '" . $shopId . "'
                             AND rrc.`profileId`= '" . $profileId . "'";
                $result = Shopware()->Db()->fetchRow($qry);

                if (!empty($result)) {
                    $paymentConfig[$payment] = $result;
                }
            }

            return $paymentConfig;
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
            foreach ($config AS $payment => $data) {
                $show[$payment] = $data['status'] == 2 ? true : false;

                $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($config);
                $validation->setAllowedCurrencies($data['currency']);
                $validation->setAllowedCountriesBilling($data['country-code-billing']);
                $validation->setAllowedCountriesDelivery($data['country-code-delivery']);

                if ($validation->isRatepayHidden()) {
                    $show[$payment]    = false;
                }

                if (!$validation->isCurrencyValid($currency)) {
                    $show[$payment]    = false;
                }

                if (!$validation->isBillingCountryValid($countryBilling)) {
                    $show[$payment]    = false;
                }

                if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                    $show[$payment]    = false;
                }

                if ($validation->isCompanyNameSet()) {
                    $show[$payment] = $data['b2b'] == '1' && $show[$payment] ? true : false;
                    $data['limit_max'] = ($data['limit_max_b2b'] > 0) ? $data['limit_max_b2b'] : $data['limit_max'];
                }

                if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                    $show[$payment]    = $data['address']    == 'yes' && $show[$payment] ? true : false;
                }

                if (Shopware()->Modules()->Basket()) {
                    $basket = Shopware()->Modules()->Basket()->sGetAmount();
                    $basket = $basket['totalAmount'];

                    Shopware()->Pluginlogger()->info('BasketAmount: ' . $basket);

                    if ($basket < $data['limit_min'] || $basket > $data['limit_max']) {
                        $show[$payment] = false;
                    }
                }
            }

            $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
            $setToDefaultPayment = false;

            $payments = array();
            foreach ($return as $payment) {
                if ($payment['name'] === 'rpayratepayinvoice' && !$show['invoice']) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Invoice');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepayinvoice" ? : $setToDefaultPayment;
                    continue;
                }
                if ($payment['name'] === 'rpayratepaydebit' && !$show['debit']) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Debit');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepaydebit" ? : $setToDefaultPayment;
                    continue;
                }
                if ($payment['name'] === 'rpayratepayrate' && !$show['installment']) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate" ? : $setToDefaultPayment;
                    continue;
                }
                if ($payment['name'] === 'rpayratepayrate0' && !$show['installment0']) {
                    Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate0');
                    $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate0" ? : $setToDefaultPayment;
                    continue;
                }
                $payments[] = $payment;
            }

            if ($setToDefaultPayment) {
                $user->setPaymentId(Shopware()->Config()->get('paymentdefault'));
                Shopware()->Models()->persist($user);
                Shopware()->Models()->flush();
                Shopware()->Models()->refresh($user);
                Shopware()->Pluginlogger()->info($user->getPaymentId());
            }

            return $payments;
        }


        /**
         * get translations
         *
         * @param int $locale
         * @param string $name
         * @return string
         */
        private function _getTranslation($locale, $name) {
            $translation = [
                2 => [
                    'accountNumber' => ['value' => 'IBAN'],
                    'bankCode' => ['value' => 'BIC'],
                    'bankdatanotvalid' => ['value' => 'Please enter a valid IBAN'],
                    'dob_info' => ['value' => 'Please enter a Date of Birth'],
                    'dobtooyoung' => ['value' => 'For the chosen payment method you have to be at least 18 years old."'],
                    'invalidAge' => ['value' => 'For the chosen payment method you have to be at least 18 years old."'],
                    'invaliddata' => ['value' => 'Please check your data'],
                    'phonenumbernotvalid' => ['value' => 'Please enter a valid telephone number.'],
                    'ratepayAgbMouseover' => ['value' => 'Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen'],
                    'ratepaySEPAAgbFirst' => ['value' => 'Ich willige hiermit in die Weiterleitung meiner Daten an RatePAY GmbH, Franklinstraße 28-29, 10587 Berlin gemäß'],
                    'ratepaySEPAAgbLast' => ['value' => 'ein und ermächtige diese, mit diesem Kaufvertrag in Zusammenhang stehende Zahlungen von meinem'],
                    'ratepaySEPAInformationHeader' => ['value' => 'RatePAY GmbH, Schlüterstr. 39, 10629 Berlin<br/>Gläubiger-ID: DE39RPY00000568463<br/>Mandatsreferenz: (wird nach Kaufabschluss übermittelt)'],
                    'transactionid' => ['value' => 'Transaction-ID'],
                    'version' => ['value' => 'Version']
                ],
                108 => [
                    'accountNumber' => ['value' => 'IBAN'],
                    'bankCode' => ['value' => 'BIC'],
                    'bankdatanotvalid' => ['value' => 'Veuillez vérifier les informations fournies.'],
                    'dob_info' => ['value' => 'Date de naissance'],
                    'dobtooyoung' => ['value' => 'Veuillez vérifier les informations fournies. Pour utiliser le moyen de paiement sélectionné, vous devez être âgé de plus de 18 ans et la date de naissance doit être renseignée selon le format TT.MM.JJJJ.\')'],
                    'invalidAge' => ['value' => 'Veuillez vérifier les informations fournies. Pour utiliser le moyen de paiement sélectionné, vous devez être âgé de plus de 18 ans et la date de naissance doit être renseignée selon le format TT.MM.JJJJ.'],
                    'invaliddata' => ['value' => 'Afin de procéder à l\'achat, veuillez indiquer le moyen de paiement et fournir les informations suivantes :'],
                    'ok' => ['value' => 'ok'],
                    'phonenumbernotvalid' => ['value' => 'Veuillez fournir un numéro de téléphone valide pour le moyen de paiement choisi.'],
                    'ratepayAgbMouseover' => ['value' => 'Veuillez vérifier les informations fournies.'],
                    'ratepaySEPAAgbFirst' => ['value' => 'Je consens par la présente à ce que mes données soient transmises à RatePAY GmbH, Franklinstraße 28-29, 
                                                    10587 Berlin conformément à la politique de confidentialité RatePAY et autorise ainsi le prélèvement automatique depuis mon 
                                                    compte mentionné ci-dessus des paiements relatifs au présent contrat. J’enjoins également mon établissement de crédit à acquitter les prélèvements automatiques depuis 
                                                    mon compte par RatePAY GmbH.'],
                    'ratepaySEPAAgbLast' => ['value' => 'Indication : 
                                                              après formation du contrat, ma référence de mandat me sera transmise par RatePAY. Je dispose de huit semaines à compter de la date de prélèvement pour exiger le remboursement du montant prélevé.
                                                              Je dispose de huit semaines à compter de la date de prélèvement pour exiger le remboursement du montant prélevé.
                                                              Les conditions ayant fait l’objet d’un accord avec mon établissement de crédit s’appliquent.
                                                              '],
                    'ratepaySEPAInformationHeader' => ['value' => 'RatePAY GmbH, Franklinstraße 28-29, 10587 Berlin
                                                                        Identifiant du créancier : DE39RPY00000568463
                                                                        Référence de mandat : (conforme à la référence transmise après conclusion de la vente
                                                                        '],
                    'transactionid' => ['value' => 'Transaction-ID'],
                    'version' => ['value' => 'Version']
                ],
                176 => [
                    'accountNumber' =>  ['value' => 'IBAN'],
                    'bankCode' =>  ['value' => 'BIC'],
                    'bankdatanotvalid' =>  ['value' => '"Om een betaling via RatePAY SEPA-incasso door te voeren, gelieve hier de IBAN invoeren'],
                    'dob_info' =>  ['value' => 'Om door RatePAY een betaling op rekening door te kunnen voeren, gelieve hier uw geboortedatum invoeren.'],
                    'dobtooyoung' =>  ['value' => 'Om door RatePAY een betaling op rekening door te kunnen, voeren moet u ten minste 18 jaar of ouder zijn.'],
                    'invalidAge' =>  ['value' => 'Om door RatePAY een betaling op rekening door te kunnen voeren, gelieve hier uw geboortedatum invoeren.'],
                    'invaliddata' =>  ['value' => 'Om door RatePAY een betaling op rekening door te kunnen voeren'],
                    'phonenumbernotvalid' =>  ['value' => 'Om door RatePAY een betaling op rekening door te kunnen voeren, gelieve hier uw telefoonnummer invoeren.'],
                    'ratepayAgbMouseover' =>  ['value' => ''],
                    'ratepaySEPAAgbFirst' =>  ['value' => 'Ik ga hiermee akkoord met het overdragen van mijn gegevens aan RatePAY GmbH, Franklinstraße 28-29, 10587 Berlin volgens het'],
                    'ratepaySEPAAgbLast' =>  ['value' => 'en machtig hen de betalingen in samenhang met deze koopovereenkomst middels een incasso van bovengenoemde rekening af te boeken. Gelijktijdig geef ik mijn kredietinstelling opdracht de incasso’s van RatePAY GmbH op mijn rekening te honoreren. '],
                    'ratepaySEPAInformationHeader' =>  ['value' => 'Opmerking. Na het tot stand komen van deze overeenkomst wordt u het RatePAY machtigingskenmerk medegedeeld. Ik kan binnen acht weken, na afschrijving, het bedrag laten terugboeken. Hierbij gelden de met mijn kredietinstelling overeengekomen voorwaarden.'],
                    'transactionid' =>  ['value' => 'Transaction-ID'],
                    'version' =>  ['value' => 'Version'],
                ],
            ];
            return $translation[$locale][$name]['value'];
        }

        /**
         * Sends a Profile_request and saves the data into the Database
         *
         * @param string $profileId
         * @param string $securityCode
         * @param int $shopId
         * @param string $country
         *
         * @return mixed
         * @throws exception
         */
        private function getRatepayConfig($profileId, $securityCode, $shopId, $country)
        {
            $factory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            $data = array(
                'profileId' => $profileId,
                'securityCode' => $securityCode);
            $response = $factory->callRequest('ProfileRequest', $data);

            $payments = array('invoice', 'elv', 'installment');

            if (is_array($response) && $response !== false) {

                foreach ($payments AS $payment) {
                    if (strstr($profileId, '_0RT') !== false) {
                        if ($payment !== 'installment') {
                            continue;
                        }
                    }

                    Shopware()->Pluginlogger()->error('B2B' . $response['result']['merchantConfig']['b2b-' . $payment]);

                    $dataPayment = array(
                        $response['result']['merchantConfig']['activation-status-' . $payment],
                        $response['result']['merchantConfig']['b2b-' . $payment] == 'yes' ? 1 : 0,
                        $response['result']['merchantConfig']['tx-limit-' . $payment . '-min'],
                        $response['result']['merchantConfig']['tx-limit-' . $payment . '-max'],
                        $response['result']['merchantConfig']['tx-limit-' . $payment . '-max-b2b'],
                        $response['result']['merchantConfig']['delivery-address-'  . $payment] == 'yes' ? 1 : 0,
                    );

                    $paymentSql = 'REPLACE INTO `rpay_ratepay_config_payment`'
                        . '(`status`, `b2b`,`limit_min`,`limit_max`,'
                        . '`limit_max_b2b`, `address`)'
                        . 'VALUES(' . substr(str_repeat('?,', 6), 0, -1) . ');';
                    try {
                        Shopware()->Db()->query($paymentSql, $dataPayment);
                        $id = Shopware()->Db()->fetchOne('SELECT `rpay_id` FROM `rpay_ratepay_config_payment` ORDER BY `rpay_id` DESC');
                        $type[$payment] = $id;
                        Shopware()->Pluginlogger()->error($payment . " " .  $id);
                    } catch (Exception $exception) {
                        Shopware()->Pluginlogger()->error($exception->getMessage());
                        return false;
                    }
                }

                if ($response['result']['merchantConfig']['activation-status-installment']  == 2) {
                    $installmentConfig = array(
                        $type['installment'],
                        $response['result']['installmentConfig']['month-allowed'],
                        $response['result']['installmentConfig']['valid-payment-firstdays'],
                        $response['result']['installmentConfig']['rate-min-normal'],
                        $response['result']['installmentConfig']['interestrate-default'],
                    );
                    $paymentSql = 'REPLACE INTO `rpay_ratepay_config_installment`'
                        . '(`rpay_id`, `month-allowed`,`payment-firstday`,`interestrate-default`,'
                        . '`rate-min-normal`)'
                        . 'VALUES(' . substr(str_repeat('?,', 5), 0, -1) . ');';
                    try {
                        Shopware()->Db()->query($paymentSql, $installmentConfig);
                    } catch (Exception $exception) {
                        Shopware()->Pluginlogger()->error($exception->getMessage());
                        return false;
                    }

                }

                if (strstr($profileId, '_0RT') !== false) {
                    $qry = "UPDATE rpay_ratepay_config SET installment0 = '" . $type['installment'] . "' WHERE profileId = '" . substr($profileId, 0, -4) . "'";
                    Shopware()->Db()->query($qry);
                } else {
                    $data = array(
                        $response['result']['merchantConfig']['profile-id'],
                        $type['invoice'],
                        $type['installment'],
                        $type['elv'],
                        0,
                        0,
                        $response['result']['merchantConfig']['eligibility-device-fingerprint'] ? : 'no',
                        $response['result']['merchantConfig']['device-fingerprint-snippet-id'],
                        strtoupper($response['result']['merchantConfig']['country-code-billing']),
                        strtoupper($response['result']['merchantConfig']['country-code-delivery']),
                        strtoupper($response['result']['merchantConfig']['currency']),
                        strtoupper($country),
                        $response['sandbox'],
                        //shopId always needs be the last line
                        $shopId
                    );

                    $activePayments[] = '"rpayratepayinvoice"';
                    $activePayments[] = '"rpayratepaydebit"';
                    $activePayments[] = '"rpayratepayrate"';
                    $activePayments[] = '"rpayratepayrate0"';

                    if (count($activePayments) > 0) {
                        $updateSqlActivePaymentMethods = 'UPDATE `s_core_paymentmeans` SET `active` = 1 WHERE `name` in(' . implode(",", $activePayments) . ') AND `active` <> 0';
                    }


                    $configSql = 'REPLACE INTO `rpay_ratepay_config`'
                        . '(`profileId`, `invoice`, `installment`, `debit`, `installment0`, `installmentDebit`,'
                        . '`device-fingerprint-status`, `device-fingerprint-snippet-id`,'
                        . '`country-code-billing`, `country-code-delivery`,'
                        . '`currency`,`country`, `sandbox`,'
                        . ' `shopId`)'
                        . 'VALUES(' . substr(str_repeat('?,', 14), 0, -1) . ');'; // In case of altering cols change 14 by amount of affected cols
                    try {
                        Shopware()->Db()->query($configSql, $data);
                        if (count($activePayments) > 0) {
                            Shopware()->Db()->query($updateSqlActivePaymentMethods);
                        }

                        return true;
                    } catch (Exception $exception) {
                        Shopware()->Pluginlogger()->error($exception->getMessage());

                        return false;
                    }
                }


            }
            else {
                Shopware()->Pluginlogger()->error('RatePAY: Profile_Request failed!');

                if (strstr($profileId, '_0RT') == false) {
                    throw new Exception('RatePAY: Profile_Request failed!');
                }
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
