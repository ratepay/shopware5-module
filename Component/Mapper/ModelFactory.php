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
     * Checkout
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory
    {


        private $_transactionId;

        private $_config;

        private $_countryCode;

        public function __construct($config = null)
        {
            $this->_config = $config;
        }

        /**
         * Returns country code by customer billing address
         *
         * @return string
         */
        private function _getCountryCodesByBillingAddress()
        {
            // Checkout address ids are set from shopware version >=5.2.0
            if (isset(Shopware()->Session()->checkoutBillingAddressId) && Shopware()->Session()->checkoutBillingAddressId > 0) {
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $checkoutAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutBillingAddressId));
                return $checkoutAddressBilling->getCountry()->getIso();
            } else {
                $shopUser = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
                $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $shopUser->getBilling()->getCountryId());
                return $country->getIso();
            }
        }

        /**
         * Gets the TransactionId for Requests
         *
         * @return string
         */
        public function getTransactionId()
        {
            return $this->_transactionId;
        }

        /**
         * Sets the TransactionId for Requests
         *
         * @param string $transactionId
         */
        public function setTransactionId($transactionId)
        {
            $this->_transactionId = $transactionId;
        }

        /**
         * Expects an instance of a paymentmodel and fill it with shopdata
         *
         * @param ObjectToBeFilled $modelName
         *
         * @return filledObjectGivenToTheFunction
         * @throws Exception The submitted Class is not supported!
         */
        public function getModel($modelName, $orderId = null)
        {
            switch ($modelName) {
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit'):
                    $this->fillPaymentInit($modelName);
                    break;
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentRequest'):
                    $this->fillPaymentRequest($modelName);
                    break;
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentConfirm'):
                    $this->fillPaymentConfirm($modelName);
                    break;
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ProfileRequest'):
                    $this->fillProfileRequest($modelName);
                    break;
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery'):
                    $this->fillConfirmationDelivery($modelName, $orderId);
                    break;
                case is_a($modelName, 'Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange'):
                    $this->fillPaymentChange($modelName, $orderId);
                    break;
                default:
                    throw new Exception('The submitted Class is not supported!');
                    break;
            }
            return $modelName;
        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit $paymentInitModel
         */
        private function fillPaymentInit(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit &$paymentInitModel
        ) {
            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setOperation('PAYMENT_INIT');

            $head->setProfileId($this->getProfileId());
            $head->setSecurityCode($this->getSecurityCode());

            $head->setSystemId(Shopware()->Shop()->getHost() ? : $_SERVER['SERVER_ADDR']);
            $head->setSystemVersion($this->_getVersion());
            $paymentInitModel->setHead($head);
        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentRequest
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentRequest $paymentRequestModel
         */
        private function fillPaymentRequest(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentRequest &$paymentRequestModel
        ) {

            $method = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::getPaymentMethod(
                Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name']
            );

            $shopUser = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

            // Checkout address ids are set in RP session from shopware version >=5.2.0
            if (isset(Shopware()->Session()->RatePAY['checkoutBillingAddressId']) && Shopware()->Session()->RatePAY['checkoutBillingAddressId'] > 0) {
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $checkoutAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutBillingAddressId']));
                $checkoutAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutShippingAddressId'] ? Shopware()->Session()->RatePAY['checkoutShippingAddressId'] : Shopware()->Session()->RatePAY['checkoutBillingAddressId']));

                $countryCodeBilling = $checkoutAddressBilling->getCountry()->getIso();
                $countryCodeShipping = $checkoutAddressShipping->getCountry()->getIso();

                $company = $checkoutAddressBilling->getCompany();
                if (empty($company)) {
                    $dateOfBirth = $shopUser->getBirthday()->format("Y-m-d"); // From Shopware 5.2 date of birth has moved to customer object
                }
                $merchantCustomerId = $shopUser->getNumber(); // From Shopware 5.2 billing number has moved to customer object
            } else {
                $checkoutAddressBilling = $shopUser->getBilling();
                $checkoutAddressShipping = $shopUser->getShipping() !== null ? $shopUser->getShipping() : $shopUser->getBilling();

                $countryBilling = Shopware()->Models()->find('Shopware\Models\Country\Country', $checkoutAddressBilling->getCountryId());
                $countryCodeBilling = $countryBilling->getIso();
                $countryShipping = Shopware()->Models()->find('Shopware\Models\Country\Country', $checkoutAddressShipping->getCountryId());
                $countryCodeShipping = $countryShipping->getIso();

                $company = $checkoutAddressBilling->getCompany();
                if (empty($company)) {
                    $dateOfBirth = $shopUser->getBilling()->getBirthday()->format("Y-m-d");
                }
                $merchantCustomerId = $shopUser->getBilling()->getNumber();
            }

            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setTransactionId(Shopware()->Session()->RatePAY['transactionId']);
            $head->setOperation('PAYMENT_REQUEST');
            $head->setProfileId($this->getProfileId());
            $head->setSecurityCode($this->getSecurityCode());
            $head->setSystemId(Shopware()->Shop()->getHost() ? : $_SERVER['SERVER_ADDR']);
            $head->setSystemVersion($this->_getVersion());
            $head->setOrderId($this->_getOrderIdFromTransactionId());
            $head->setMerchantConsumerId($merchantCustomerId);

            //set device ident token if available
            if (Shopware()->Session()->RatePAY['dfpToken']) {
                $head->setDeviceToken(Shopware()->Session()->RatePAY['dfpToken']);
            }

            $customer = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Customer();

            // only for elv and sepa elv, or for rate elv
            if ($method === 'ELV' ||
                ($method === 'INSTALLMENT' && Shopware()->Session()->RatePAY['ratenrechner']['payment_firstday'] == 2)
            ) {
                $bankAccount = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount();

                $bankAccount->setBankAccount(Shopware()->Session()->RatePAY['bankdata']['account']);
                $bankAccount->setBankCode(Shopware()->Session()->RatePAY['bankdata']['bankcode']);
                $bankAccount->setOwner(Shopware()->Session()->RatePAY['bankdata']['bankholder']);

                $customer->setBankAccount($bankAccount);
            }

            $customer->setFirstName($checkoutAddressBilling->getFirstName());
            $customer->setLastName($checkoutAddressBilling->getLastName());
            $customer->setEmail($shopUser->getEmail());

            if (!empty($company)) {
                $customer->setCompanyName($checkoutAddressBilling->getCompany());
                $customer->setVatId($checkoutAddressBilling->getVatId());
            } else {
                $customer->setDateOfBirth($dateOfBirth);
            }

            /**
             * set gender and salutation based on the given billingaddress salutation
             */
            $gender = 'U';
            if ($checkoutAddressBilling->getSalutation() === 'mr') {
                $gender = 'M';
                $customer->setSalutation('Herr');
            } elseif ($checkoutAddressBilling->getSalutation() === 'ms') {
                $gender = 'F';
                $customer->setSalutation('Frau');
            } else {
                $customer->setSalutation($checkoutAddressBilling->getSalutation());
            }

            $customer->setGender($gender);
            $customer->setPhone($checkoutAddressBilling->getPhone());
            $customer->setNationality($this->_countryCode);
            $customer->setIpAddress($this->_getCustomerIP());

            $customer->setBillingAddresses($this->_getCheckoutAddress(
                $checkoutAddressBilling,
                'BILLING',
                $countryCodeBilling
            ));
            $customer->setShippingAddresses($this->_getCheckoutAddress(
                $checkoutAddressShipping,
                'DELIVERY',
                $countryCodeShipping
            ));

            $payment = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Payment();
            $payment->setAmount($this->getAmount());
            $payment->setCurrency(Shopware()->Currency()->getShortName());
            $payment->setMethod($method);

            if ($method === 'INSTALLMENT') {
                $payment->setAmount(Shopware()->Session()->RatePAY['ratenrechner']['total_amount']);

                if (Shopware()->Session()->RatePAY['ratenrechner']['payment_firstday'] == 2) {
                    $payment->setDirectPayType('DIRECT-DEBIT');
                } else {
                    $payment->setDirectPayType('BANK-TRANSFER');
                }

                $payment->setPaymentFirstday(Shopware()->Session()->RatePAY['ratenrechner']['payment_firstday']);
                $payment->setInstallmentAmount(Shopware()->Session()->RatePAY['ratenrechner']['rate']);
                $payment->setInstallmentNumber(Shopware()->Session()->RatePAY['ratenrechner']['number_of_rates']);
                $payment->setInterestRate(Shopware()->Session()->RatePAY['ratenrechner']['interest_rate']);
                $payment->setLastInstallmentAmount(Shopware()->Session()->RatePAY['ratenrechner']['last_rate']);
            }

            $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
            $basket->setAmount($this->getAmount());
            $basket->setCurrency(Shopware()->Currency()->getShortName());
            $shopItems = Shopware()->Session()->sOrderVariables['sBasket']['content'];
            $items = array();
            foreach ($shopItems as $shopItem) {
                $item = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();
                $item->setArticleName($shopItem['articlename']);
                $item->setArticleNumber($shopItem['ordernumber']);
                $item->setQuantity($shopItem['quantity']);
                $item->setTaxRate($shopItem['tax_rate']);
                $item->setUnitPriceGross($shopItem['priceNumeric']);
                $items[] = $item;
            }
            if (Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'] > 0) {
                $items[] = $this->getShippingAsItem(
                    Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'],
                    Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax']
                );
            }
            $basket->setItems($items);

            $paymentRequestModel->setHead($head);
            $paymentRequestModel->setCustomer($customer);
            $paymentRequestModel->setPayment($payment);
            $paymentRequestModel->setShoppingBasket($basket);

        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentConfirm
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentConfirm $paymentConfirmModel
         */
        private function fillPaymentConfirm(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentConfirm &$paymentConfirmModel
        ) {
            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setOperation('PAYMENT_CONFIRM');
            $head->setProfileId($this->getProfileId());
            $head->setSecurityCode($this->getSecurityCode());
            $head->setSystemId(Shopware()->Shop()->getHost() ? : $_SERVER['SERVER_ADDR']);
            $head->setTransactionId($this->getTransactionId());
            $head->setSystemVersion($this->_getVersion());
            $head->setOrderId($this->_getOrderIdFromTransactionId());
            $paymentConfirmModel->setHead($head);
        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ProfileRequest
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ProfileRequest $profileRequestModel
         */
        private function fillProfileRequest(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ProfileRequest &$profileRequestModel, $profileId = null, $securityCode = null
        ) {
            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setOperation('PROFILE_REQUEST');
            $head->setProfileId($profileId);
            $head->setSecurityCode($securityCode);
            $head->setSystemId(
                Shopware()->Db()->fetchOne(
                    "SELECT `host` FROM `s_core_shops` WHERE `default`=1"
                ) ? : $_SERVER['SERVER_ADDR']
            );
            $head->setSystemVersion($this->_getVersion());
            $profileRequestModel->setHead($head);
        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery $confirmationDeliveryModel
         */
        private function fillConfirmationDelivery(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery &$confirmationDeliveryModel, $orderId
        ) {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
            $countryCode = $order->getBilling()->getCountry()->getIso();

            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setOperation('CONFIRMATION_DELIVER');
            $head->setProfileId($this->getProfileId($countryCode));
            $head->setSecurityCode($this->getSecurityCode($countryCode));
            $head->setSystemId(
                Shopware()->Db()->fetchOne(
                    "SELECT `host` FROM `s_core_shops` WHERE `default`=1"
                ) ? : $_SERVER['SERVER_ADDR']
            );
            $head->setSystemVersion($this->_getVersion());
            $confirmationDeliveryModel->setHead($head);
        }

        /**
         * Fills an object of the class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange $paymentChangeModel
         */
        private function fillPaymentChange(
            Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange &$paymentChangeModel, $orderId
        ) {

            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
            $countryCode = $order->getBilling()->getCountry()->getIso();

            $head = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head();
            $head->setOperation('PAYMENT_CHANGE');
            $head->setTransactionId($this->_transactionId);
            $head->setProfileId($this->getProfileId($countryCode));
            $head->setSecurityCode($this->getSecurityCode($countryCode));
            $head->setSystemId(
                Shopware()->Db()->fetchOne(
                    "SELECT `host` FROM `s_core_shops` WHERE `default`=1"
                ) ? : $_SERVER['SERVER_ADDR']
            );
            $head->setSystemVersion($this->_getVersion());

            $order = Shopware()->Db()->fetchRow(
                "SELECT `name`,`currency` FROM `s_order` "
                . "INNER JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id` = `s_order`.`paymentID` "
                . "WHERE `s_order`.`transactionID`=?;",
                array($this->_transactionId)
            );

            $paymentChangeModel->setHead($head);
        }

        /**
         * Return the full amount to pay.
         *
         * @return float
         */
        public function getAmount()
        {
            $user = Shopware()->Session()->sOrderVariables['sUserData'];
            $basket = Shopware()->Session()->sOrderVariables['sBasket'];
            if (!empty($user['additional']['charge_vat'])) {
                return empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
            } else {
                return $basket['AmountNetNumeric'];
            }
        }

        /**
         * Returns the Shippingcosts as Item
         *
         * @param string $amount
         * @param string $tax
         *
         * @return \Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item
         */
        private function getShippingAsItem($amount, $tax)
        {
            $item = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();
            $item->setArticleName('shipping');
            $item->setArticleNumber('shipping');
            $item->setQuantity(1);
            $item->setTaxRate($tax);
            $item->setUnitPriceGross($amount);

            return $item;
        }

        /**
         * Returns the OrderID for the TransactionId set to this Factory
         *
         * @return string $returnValue
         */
        private function _getOrderIdFromTransactionId()
        {
            $returnValue = null;
            if (!empty($this->_transactionId)) {
                $returnValue = Shopware()->Db()->fetchOne(
                    "SELECT `ordernumber` FROM `s_order` "
                    . "INNER JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id` = `s_order`.`paymentID` "
                    . "WHERE `s_order`.`transactionID`=?;",
                    array($this->_transactionId)
                );
            }

            return $returnValue;
        }

        /**
         * Returns the Version for this Payment-Plugin
         *
         * @return string
         */
        private function _getVersion()
        {
            $boostrap = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrap();

            return Shopware()->Config()->get('version') . '_' . $boostrap->getVersion();
        }

        /**
         * Returns the IP Address for the current customer
         *
         * @return string
         */
        private function _getCustomerIP()
        {
            $customerIp = null;
            if (!is_null(Shopware()->Front())) {
                $customerIp = Shopware()->Front()->Request()->getClientIp();
            } else {
                $customerIp = Shopware()->Db()->fetchOne(
                    "SELECT `remote_addr` FROM `s_order` WHERE `transactionID`=" . $this->_transactionId
                );
            }

            return $customerIp;
        }

        /**
         * Transfer checkout address to address model
         *
         * @param $checkoutAddress
         * @param $type
         * @param $countryCode
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
         */
        function _getCheckoutAddress($checkoutAddress, $type, $countryCode) {
            $address = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address();
            $address->setType($type);
            $address->setFirstName($checkoutAddress->getFirstName());
            $address->setLastName($checkoutAddress->getLastName());
            $address->setCompany($checkoutAddress->getCompany());
            $address->setCity($checkoutAddress->getCity());
            $address->setStreet($checkoutAddress->getStreet());
            $address->setZipCode($checkoutAddress->getZipCode());
            $address->setCountryCode($countryCode);
            return $address;
        }

        public function getProfileId($countryCode = false)
        {
            if (!$countryCode) {
                $countryCode = $this->_getCountryCodesByBillingAddress();
            }

            if(null !== $this->_config) {
                $profileId = $this->_config->get('RatePayProfileID' . $countryCode);
            } else{
                $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $countryCode);
            }

            return $profileId;
        }

        public function getSecurityCode($countryCode = false)
        {
            if (!$countryCode) {
                $countryCode = $this->_getCountryCodesByBillingAddress();
            }

            if(null !== $this->_config) {
                $securityCode = $this->_config->get('RatePaySecurityCode' . $countryCode);
            } else {
                $securityCode = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePaySecurityCode' . $countryCode);
            }

            return $securityCode;
        }

    }
