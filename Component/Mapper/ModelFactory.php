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

        private $_sandboxMode;

        private $_logging;

        private $_orderId = false;

        private $_zPercent = false;

        private $_retry = false;

        private $_object;

        public function __construct($config = null)
        {
            $this->_config = $config;
        }

        public function setOrderId($orderId)
        {
            $this->_orderId = $orderId;
        }

        public function setZPercent() {
            $this->_zPercent = true;
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
         * call operation
         *
         * @param string $operationType
         * @param array $operationData
         *
         * @return bool|array
         */
        public function callRequest($operationType, array $operationData = []) {
            $this->_logging = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();

            switch ($operationType) {
                case 'ProfileRequest':
                    return $this->callProfileRequest($operationData);
                    break;
                case 'PaymentRequest':
                    return $this->callPaymentRequest();
                    break;
                case 'ConfirmationDeliver':
                    return $this->callConfirmationDeliver($operationData);
                    break;
                case 'PaymentChange':
                    return $this->callPaymentChange($operationData);
                    break;
                case 'PaymentConfirm':
                    return $this->callPaymentConfirm();
                    break;
                case 'CalculationRequest':
                    return $this->callCalculationRequest($operationData);
                    break;
            }
        }

        private function callCalculationRequest($operationData) {
            $mbHead = $this->getHead();
            $mbContent = new RatePAY\ModelBuilder('Content');

            $calcArray['Amount'] = $operationData['payment']['amount'];
            $calcArray['PaymentFirstday'] = $operationData['payment']['paymentFirstday'];
            if ($operationData['subtype'] == 'calculation-by-time') {
                $calcArray['CalculationTime']['Month'] = $operationData['payment']['month'];
            } else {
                $calcArray['CalculationRate']['Rate'] = $operationData['payment']['rate'];
            }

            $mbContent->setArray(['InstallmentCalculation' => $calcArray]);
            $rb = new RatePAY\RequestBuilder($this->isSandboxMode());

            $calculationRequest = $rb->callCalculationRequest($mbHead, $mbContent)->subtype($operationData['subtype']);
            $this->_logging->logRequest($calculationRequest->getRequestRaw(), $calculationRequest->getResponseRaw());

            return $calculationRequest;
        }

        /**
         * call payment confirm
         *
         * @return bool
         */
        private function callPaymentConfirm()
        {
            $mbHead = $this->getHead();
            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true

            $paymentConfirm = $rb->callPaymentConfirm($mbHead);
            $this->_logging->logRequest($paymentConfirm->getRequestRaw(), $paymentConfirm->getResponseRaw());

            if ($paymentConfirm->isSuccessful()) {
                return true;
            }
            return false;
        }

        /**
         * get request head
         *
         * @param bool $countryCode
         * @return \RatePAY\ModelBuilder
         */
        private function getHead($countryCode = false) {
            $systemId = $this->getSystemId();
            $bootstrap = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrap();

            $head = [
                'SystemId' => $systemId,
                'Credential' => [
                    'ProfileId' => $this->getProfileId($countryCode),
                    'Securitycode' => $this->getSecurityCode($countryCode)
                ],
                'Meta' => [
                    'Systems' => [
                        'System' => [
                            'Name' => 'Shopware',
                            'Version' => Shopware()->Config()->get('version') . '/' . $bootstrap->getVersion()
                        ]
                    ]
                ]
            ];

            $orderId = $this->_orderId;
            if (!empty($orderId)) {
                $head['External']['OrderId'] = $this->_orderId;
            }

            $this->_sandboxMode = $this->getSandboxMode($countryCode);

            $mbHead = new \RatePAY\ModelBuilder('head');
            $mbHead->setArray($head);

            $transactionId = $this->_transactionId;
            if (!empty($transactionId)) {
                $mbHead->setTransactionId($this->_transactionId);
            }

            return $mbHead;
        }

        /**
         * get sandbox mode
         *
         * @param $countryCode
         * @return int
         */
        public function getSandboxMode($countryCode) {
            $profileId = $this->getProfileId($countryCode);
            if (strstr($profileId, '_0RT') !== false) {
                $profileId = substr($profileId, 0, -4);
            }

            $qry = 'SELECT sandbox FROM rpay_ratepay_config WHERE profileId = "'. $profileId .'"';
            $sandbox = Shopware()->Db()->fetchOne($qry);
            return $sandbox;
        }

        /**
         * call payment request
         *
         * @return mixed
         */
        private function callPaymentRequest()
        {
            $method = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::getPaymentMethod(
                Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name']
            );

            if ($method == 'INSTALLMENT0') {
                $this->setZPercent();
                $method = 'INSTALLMENT';
            }

            $mbHead = $this->getHead();
            $mbHead->setCustomerDevice(
                $mbHead->CustomerDevice()->setDeviceToken(Shopware()->Session()->RatePAY['dfpToken'])
            );

            $shopUser = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

            // Checkout address ids are set in RP session from shopware version >=5.2.0
            if (isset(Shopware()->Session()->RatePAY['checkoutBillingAddressId']) && Shopware()->Session()->RatePAY['checkoutBillingAddressId'] > 0) {
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $checkoutAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutBillingAddressId']));
                $checkoutAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutShippingAddressId'] ? Shopware()->Session()->RatePAY['checkoutShippingAddressId'] : Shopware()->Session()->RatePAY['checkoutBillingAddressId']));

                $company = $checkoutAddressBilling->getCompany();
                if (empty($company)) {
                    if ($this->existsAndNotEmpty($shopUser, 'getBirthday')) {
                        $dateOfBirth = $shopUser->getBirthday()->format("Y-m-d"); // From Shopware 5.2 date of birth has moved to customer object
                    } else {
                        $checkoutAddressBirthBilling = $shopUser->getBilling();
                        if ($this->existsAndNotEmpty($checkoutAddressBirthBilling, 'getBirthday')) {
                            $dateOfBirth = $checkoutAddressBirthBilling->getBirthday()->format("Y-m-d");
                        }
                        if ($this->existsAndNotEmpty($checkoutAddressBilling, 'getBirthday')) {
                            $dateOfBirth = $checkoutAddressBilling->getBirthday()->format("Y-m-d");
                        } elseif (empty($dateOfBirth)) {
                            $dateOfBirth = '0000-00-00';
                        }
                    }
                }
            } else {
                $checkoutAddressBilling = $shopUser->getBilling();
                $checkoutAddressShipping = $shopUser->getShipping() !== null ? $shopUser->getShipping() : $shopUser->getBilling();

                $company = $checkoutAddressBilling->getCompany();
                if (empty($company)) {
                    if ($this->existsAndNotEmpty($checkoutAddressBilling, 'getBirthday')) {
                        $dateOfBirth = $checkoutAddressBilling->getBirthday()->format("Y-m-d");
                    } else{
                        if ($this->existsAndNotEmpty($shopUser, 'getBirthday')) {
                            $dateOfBirth = $shopUser->getBirthday()->format("Y-m-d");
                        } elseif (empty($dateOfBirth)) {
                            $dateOfBirth = '0000-00-00';
                        }
                    }

                }
            }

            if ($this->existsAndNotEmpty($shopUser, "getNumber")) { // From Shopware 5.2 billing number has moved to customer object
                $merchantCustomerId = $shopUser->getNumber();
            } elseif ($this->existsAndNotEmpty($checkoutAddressBilling, "getNumber")) {
                $merchantCustomerId = $checkoutAddressBilling->getNumber();
            }

            if ($this->existsAndNotEmpty($checkoutAddressBilling, "getCountry") && $this->existsAndNotEmpty($checkoutAddressBilling->getCountry(), "getIso")) {
                $countryCodeBilling = $checkoutAddressBilling->getCountry()->getIso();
                $countryCodeShipping = $checkoutAddressShipping->getCountry()->getIso();
            } elseif ($this->existsAndNotEmpty($checkoutAddressBilling, "getCountryId")) {
                $countryBilling = Shopware()->Models()->find('Shopware\Models\Country\Country', $checkoutAddressBilling->getCountryId());
                $countryCodeBilling = $countryBilling->getIso();
                $countryShipping = Shopware()->Models()->find('Shopware\Models\Country\Country', $checkoutAddressShipping->getCountryId());
                $countryCodeShipping = $countryShipping->getIso();
            }

            $mbHead->setArray([
                'External' => [
                    'MerchantConsumerId' => $merchantCustomerId,
                    'OrderId' => $this->_getOrderIdFromTransactionId()
                ]
            ]);

            $gender = 'u';
            if ($checkoutAddressBilling->getSalutation() === 'mr') {
                $gender = 'm';
                $salutation = 'Herr';
            } elseif ($checkoutAddressBilling->getSalutation() === 'ms') {
                $gender = 'f';
                $salutation = 'Frau';
            } else {
                $salutation = $checkoutAddressBilling->getSalutation();
            }

            if ($method === 'INSTALLMENT') {
                $installmentDetails = $this->getPaymentDetails();
            }

            $shopItems = Shopware()->Session()->sOrderVariables['sBasket']['content'];
            $shoppingBasket = $this->createBasketArray($shopItems);

            if (Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'] > 0) {
                $system = Shopware()->System();
                $usergroup = Shopware()->Db()->fetchRow('
                    SELECT * FROM s_core_customergroups
                    WHERE groupkey = ?
                    ', [$system->sUSERGROUP]);

                $shoppingBasket['Shipping'] = array(
                    'Description' => "Shipping costs",
                    'UnitPriceGross' => Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'],
                    'TaxRate' => Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax'],

                );

                if ($usergroup['tax'] == 0) {
                    $cost =  Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'];
                    $tax = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax'];
                    $shoppingBasket['Shipping']['UnitPriceGross'] = number_format($cost / 100 * $tax +  $cost , 2);
                }
            }

            $shopContext = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $lang = $shopContext->getShop()->getLocale()->getLocale();
            $lang = substr($lang, 0, 2);

            $mbContent = new \RatePAY\ModelBuilder('Content');
            $contentArr = [
                'Customer' => [
                    'Gender' => $gender,
                    'Salutation' => $salutation,
                    'FirstName' => $checkoutAddressBilling->getFirstName(),
                    'LastName' => $checkoutAddressBilling->getLastName(),
                    'Language' => strtolower($lang),
                    'DateOfBirth' => $dateOfBirth,
                    'IpAddress' => $this->_getCustomerIP(),
                    'Addresses' => [
                        [
                            'Address' => $this->_getCheckoutAddress($checkoutAddressBilling, 'BILLING', $countryCodeBilling)
                        ], [
                            'Address' => $this->_getCheckoutAddress($checkoutAddressShipping, 'DELIVERY', $countryCodeShipping)
                        ]

                    ],
                    'Contacts' => [
                        'Email' => $shopUser->getEmail(),
                        'Phone' => [
                            'DirectDial' => $checkoutAddressBilling->getPhone()
                        ],
                    ],
                ],
                'ShoppingBasket' => $shoppingBasket,
                'Payment' => [
                    'Method' => strtolower($method),
                    'Amount' => $this->getAmount(),
                ]
            ];

            if (!empty($company)) {
                $contentArr['Customer']['CompanyName'] = $checkoutAddressBilling->getCompany();
                $contentArr['Customer']['VatId'] = $checkoutAddressBilling->getVatId();
            }
            $elv = false;
            if (!empty($installmentDetails)) {
                if (Shopware()->Session()->RatePAY['ratenrechner']['payment_firstday'] == 28) {
                    $contentArr['Payment']['DebitPayType'] = 'BANK-TRANSFER';
                } else {
                    $contentArr['Payment']['DebitPayType'] = 'DIRECT-DEBIT';
                    $elv = true;

                }
                $contentArr['Payment']['Amount'] = Shopware()->Session()->RatePAY['ratenrechner']['total_amount'];
                $contentArr['Payment']['InstallmentDetails'] = $installmentDetails;
            }

            if ($method === 'ELV' || ($method == 'INSTALLMENT' && $elv == true)) {
                $contentArr['Customer']['BankAccount']['Owner'] = Shopware()->Session()->RatePAY['bankdata']['bankholder'];

                $bankCode = Shopware()->Session()->RatePAY['bankdata']['bankcode'];
                if (!empty($bankCode)) {
                    $contentArr['Customer']['BankAccount']['BankAccountNumber'] = Shopware()->Session()->RatePAY['bankdata']['account'];
                    $contentArr['Customer']['BankAccount']['BankCode'] = Shopware()->Session()->RatePAY['bankdata']['bankcode'];
                } else {
                    $contentArr['Customer']['BankAccount']['Iban'] = Shopware()->Session()->RatePAY['bankdata']['account'];
                }
            }

            $mbContent->setArray($contentArr);

            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode());
            $paymentRequest = $rb->callPaymentRequest($mbHead, $mbContent);
            $this->_logging->logRequest($paymentRequest->getRequestRaw(), $paymentRequest->getResponseRaw());

            return $paymentRequest;
        }

        /**
         * check if method of object exists and not null
         *
         * @param $method
         * @return bool
         */
        private function existsAndNotEmpty(&$object, $method) {
            $check = (method_exists($object, $method) && !empty($object->$method()) && !is_null($object->$method()));
            return (bool)$check;
        }

        /**
         * get payment details
         *
         * @return array
         */
        private function getPaymentDetails() {
            $paymentDetails = array();

            $paymentDetails['InstallmentNumber'] = Shopware()->Session()->RatePAY['ratenrechner']['number_of_rates'];
            $paymentDetails['InstallmentAmount'] = Shopware()->Session()->RatePAY['ratenrechner']['rate'];
            $paymentDetails['LastInstallmentAmount'] = Shopware()->Session()->RatePAY['ratenrechner']['last_rate'];
            $paymentDetails['InterestRate'] = Shopware()->Session()->RatePAY['ratenrechner']['interest_rate'];
            $paymentDetails['PaymentFirstday'] = Shopware()->Session()->RatePAY['ratenrechner']['payment_firstday'];

            return $paymentDetails;
        }

        /**
         * @param $operationData
         * @return bool|array
         */
        private function callProfileRequest($operationData)
        {
            $systemId = $this->getSystemId();
            $sandbox = true;
            $bootstrap = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrap();

            if (strpos($operationData['profileId'], '_TE_')) {
                $sandbox = true;
            } elseif (strpos($operationData['profileId'], '_PR_')) {
                $sandbox = false;
            }

            $mbHead = new \RatePAY\ModelBuilder();
            $mbHead->setArray([
                'SystemId' => $systemId,
                'Credential' => [
                    'ProfileId' => $operationData['profileId'],
                    'Securitycode' => $operationData['securityCode']
                ],
                'Meta' => [
                    'Systems' => [
                        'System' => [
                            'Name' => 'Shopware',
                            'Version' => Shopware()->Config()->get('version') . '/' . $bootstrap->getVersion()
                        ]
                    ]
                ]
            ]);

            $rb = new \RatePAY\RequestBuilder($sandbox);

            $profileRequest = $rb->callProfileRequest($mbHead);
            $this->_logging->logRequest($profileRequest->getRequestRaw(), $profileRequest->getResponseRaw());

            if ($sandbox == true && $profileRequest->getReasonCode() == 120) {
                $sandbox = false;

                $rb = new \RatePAY\RequestBuilder($sandbox);

                $profileRequest = $rb->callProfileRequest($mbHead);
                $this->_logging->logRequest($profileRequest->getRequestRaw(), $profileRequest->getResponseRaw());

            }
            if ($profileRequest->isSuccessful()) {
                if ($sandbox == true) {
                    $sandbox = 1;
                } else {
                    $sandbox = 0;
                }
                return array('result' =>$profileRequest->getResult(), 'sandbox' => $sandbox);
            }
            return false;
        }

        /**
         * is sandbox mode
         *
         * @return bool
         */
        public function isSandboxMode()
        {
            if ($this->_sandboxMode == 1) {
                return true;
            }
            return false;
        }

        /**
         * get system id
         *
         * @return mixed
         */
        private function getSystemId()
        {

            $systemId = Shopware()->Db()->fetchOne("SELECT `host` FROM `s_core_shops` WHERE `default`=1") ? : $_SERVER['SERVER_ADDR'];

            return $systemId;
        }

        /**
         * create basket array
         *
         * @param $items
         * @param $type
         * @return array
         */
        private function createBasketArray($items, $type = false) {
            $shoppingBasket = array();
            $item = array();
            $net = false;
            $orderId = $this->_orderId;

            if (empty($orderId)) {
                $system = Shopware()->System();
                $usergroup = Shopware()->Db()->fetchRow('
                        SELECT * FROM s_core_customergroups
                        WHERE groupkey = ?
                        ', [$system->sUSERGROUP]);
            } else {
                $user = Shopware()->Db()->fetchRow('
                        SELECT * FROM s_order
                        WHERE ordernumber = ?
                        ', $this->_orderId);
                $usergroupId = Shopware()->Db()->fetchRow('
                        SELECT * FROM s_user
                        WHERE id = ?
                        ', $user['userID']);
                $usergroup = Shopware()->Db()->fetchRow('
                        SELECT * FROM s_core_customergroups
                        WHERE groupkey = ?
                        ', $usergroupId['customergroup']);
            }

            $b2b = Shopware()->Db()->fetchRow('
                        SELECT company FROM s_user_billingaddress
                        WHERE userID = ?
                        ', $user['userID']);

            if ((int)$usergroup['tax'] === 0 && !empty($b2b['company'])) {
                $net = true;
            }

            foreach ($items AS $shopItem) {
                if ($shopItem->articlenumber == 'shipping') {
                    if ($shopItem->delivered == 0 || $shopItem->cancelled == 0 || $shopItem->returned == 0) {
                        if ($this->_retry == true) {
                            $item = array(
                                'ArticleNumber' => $shopItem->articlenumber,
                                'Quantity' => 1,
                                'Description' => "shipping",
                                'UnitPriceGross' => $shopItem->price,
                                'TaxRate' => $shopItem->taxRate,
                            );
                            $shoppingBasket['Items'][] = array('Item' => $item);
                        } else {
                            $shoppingBasket['Shipping'] = array(
                                'Description' => "Shipping costs",
                                'UnitPriceGross' => $shopItem->price,
                                'TaxRate' => $shopItem->taxRate,
                            );
                        }
                    }
                    if (!empty($type) && $shopItem->cancelledItems == 0 && $shopItem->returnedItems == 0 && $shopItem->deliveredItems == 0) {
                        unset($shoppingBasket['Shipping']);
                    }
                } elseif ((substr($shopItem->articlenumber, 0, 5) == 'Debit')
                    || (substr($shopItem->articlenumber, 0, 6) == 'Credit')) {
                    if ($this->_retry == true || $shopItem->price > 0) {
                        $item = array(
                            'ArticleNumber' => $shopItem->articleordernumber,
                            'Quantity' => $shopItem->quantity,
                            'Description' => $shopItem->articlenumber,
                            'UnitPriceGross' => $shopItem->price,
                            'TaxRate' => $shopItem->taxRate,
                        );
                    } else {
                        $shoppingBasket['Discount'] = array(
                            'Description' => $shopItem->articlenumber,
                            'UnitPriceGross' => $shopItem->price,
                            'TaxRate' => $shopItem->taxRate,
                        );
                    }
                } else {
                    if (is_array($shopItem)) {
                        if ($shopItem['quantity'] == 0 && empty($type)) {
                            continue;
                        }
                        if ($shopItem['articlename'] == 'Shipping') {
                            if ($this->_retry == true) {
                                $item = array(
                                    'ArticleNumber' => "shipping",
                                    'Quantity' => 1,
                                    'Description' => 'shipping',
                                    'UnitPriceGross' => $shopItem['priceNumeric'],
                                    'TaxRate' => $shopItem['tax_rate'],
                                );
                            } else {
                                $shoppingBasket['Shipping'] = array(
                                    'Description' => "Shipping costs",
                                    'UnitPriceGross' => $shopItem['priceNumeric'],
                                    'TaxRate' => $shopItem['tax_rate'],
                                );
                                continue;
                            }
                        } else {
                            $item = array(
                                'Description' => $shopItem['articlename'],
                                'ArticleNumber' => $shopItem['ordernumber'],
                                'Quantity' => $shopItem['quantity'],
                                'UnitPriceGross' => $shopItem['priceNumeric'],
                                'TaxRate' => $shopItem['tax_rate'],
                            );
                            if ($net == true) {
                                $price = $shopItem['priceNumeric']/100 * $shopItem['tax_rate'] +  $shopItem['priceNumeric'];
                                $item['UnitPriceGross'] = $shopItem['priceNumeric'];
                            }
                        }
                    } elseif (is_object($shopItem)) {
                        if (!isset($shopItem->name)) {
                            if ($shopItem->getQuantity() == 0 && empty($type)) {
                                continue;
                            }
                            $item = array(
                                'Description' => $shopItem->getArticleName(),
                                'ArticleNumber' => $shopItem->getArticleNumber(),
                                'Quantity' => $shopItem->getQuantity(),
                                'UnitPriceGross' => $shopItem->getPrice(),
                                'TaxRate' => $shopItem->getTaxRate(),
                            );
                            if ($net == true) {
                                $item['UnitPriceGross'] = $shopItem->getNetPrice();
                            }
                            $type = false;
                        } else {
                            if ($shopItem->quantity == 0 && empty($type)) {
                                continue;
                            }
                            $item = array(
                                'Description' => $shopItem->name,
                                'ArticleNumber' => $shopItem->articlenumber,
                                'Quantity' => $shopItem->quantity,
                                'UnitPriceGross' => $shopItem->price,
                                'TaxRate' => $shopItem->taxRate,
                            );
                        }

                        if (!empty($type)) {
                            switch ($type) {
                                case 'return':
                                    if ($shopItem->returnedItems == 0) {
                                        $item['Quantity'] = 0;
                                        continue;
                                    }
                                    $item['Quantity'] = $shopItem->returnedItems;
                                    break;
                                case 'cancellation':
                                    if ($shopItem->cancelledItems == 0) {
                                        $item['Quantity'] = 0;
                                        continue;
                                    }
                                    $item['Quantity'] = $shopItem->cancelledItems;
                                    break;
                                case 'shippingRate':
                                    if ($shopItem->maxQuantity == 0) {
                                        $item['Quantity'] = 0;
                                        continue;
                                    }
                                    $item['Quantity'] = $shopItem->maxQuantity;
                                    break;
                            }
                        }
                    }

                    if ($item['Quantity'] != 0) {
                        $shoppingBasket['Items'][] = array('Item' => $item);
                    }
                }
            }
            return $shoppingBasket;
        }

        /**
         * call confirmation deliver
         *
         * @param $operationData
         * @return bool
         */
        private function callConfirmationDeliver($operationData)
        {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $operationData['orderId']);
            $countryCode = $order->getBilling()->getCountry()->getIso();
            $method = $order->getPayment()->getName();
            $type = 'shipping';

            if ($method == 'rpayratepayrate0') {
                $this->setZPercent();
            }
            if ($method == 'rpayratepayrate0' || $method == 'rpayratepayrate') {
                $type = 'shippingRate';
            }

            $mbHead = $this->getHead($countryCode);

            $shoppingItems = $this->createBasketArray($operationData['items'], $type);
            $shoppingBasket = [
                'ShoppingBasket' => $shoppingItems,
            ];

            $mbContent = new \RatePAY\ModelBuilder('Content');
            $mbContent->setArray($shoppingBasket);

            $documentModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document');
            $document = $documentModel->findOneBy(array('orderId' => $operationData['orderId'], 'type' => 1));

            if (!is_null($document)) {
                $dateObject = new DateTime();
                $currentDate = $dateObject->format("Y-m-d");
                $currentTime = $dateObject->format("H:m:s");
                $currentDateTime = $currentDate . "T" . $currentTime;

                $invoicing = [
                    'Invoicing' => [
                        'InvoiceId' => $document->getDocumentId(),
                        'InvoiceDate' => $currentDateTime,
                        'DeliveryDate' => $currentDateTime,
                        //'DueDate' => date('Y-m-d\Th:m:s'),
                    ]
                ];
                $mbContent->setArray($invoicing);
            }
            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true
            $confirmationDeliver = $rb->callConfirmationDeliver($mbHead, $mbContent);
            $this->_logging->logRequest($confirmationDeliver->getRequestRaw(), $confirmationDeliver->getResponseRaw());

            if ($confirmationDeliver->isSuccessful()) {
                return true;
            } elseif ($this->_retry == false && (int)$confirmationDeliver->getReasonCode() == 2300) {
                $this->_retry = true;
                return $this->callRequest('ConfirmationDeliver', $operationData);
            }
            return false;

        }

        /**
         * call a payment change (return, cancellation, order change)
         *
         * @param $operationData
         * @return bool
         */
        private function callPaymentChange($operationData)
        {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $operationData['orderId']);
            $countryCode = $order->getBilling()->getCountry()->getIso();
            $method = $order->getPayment()->getName();

            if ($method == 'rpayratepayrate0') {
                $this->setZPercent();
            }

            $mbHead = $this->getHead($countryCode);

            if ($operationData['subtype'] == 'credit') {
                if ($operationData['items']['price'] > 0) {
                    $shoppingItems['Items'] = array('Item' => $item = array(
                        'ArticleNumber' => $operationData['items']['articleordernumber'],
                        'Quantity' => 1,
                        'Description' => $operationData['items']['name'],
                        'UnitPriceGross' => $operationData['items']['price'],
                        'TaxRate' => $operationData['items']['tax_rate'],
                    ));
                } else {
                    $shoppingItems = array('Discount' => $item = array(
                        'Description' => $operationData['items']['name'],
                        'UnitPriceGross' => $operationData['items']['price'],
                        'TaxRate' => $operationData['items']['tax_rate']
                    ));
                }

            } else {
                $shoppingItems = $this->createBasketArray($operationData['items'], $operationData['subtype']);
            }

            $shoppingBasket = [
                'ShoppingBasket' => $shoppingItems,
            ];

            $mbContent = new \RatePAY\ModelBuilder('Content');
            $mbContent->setArray($shoppingBasket);

            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true
            $paymentChange = $rb->callPaymentChange($mbHead, $mbContent)->subtype($operationData['subtype']);
            $this->_logging->logRequest($paymentChange->getRequestRaw(), $paymentChange->getResponseRaw());

            if ($paymentChange->isSuccessful()) {
                return true;
            } elseif ($this->_retry == false && (int)$paymentChange->getReasonCode() == 2300) {
                $this->_retry = true;
                return $this->callRequest('PaymentChange', $operationData);
            }
            return false;
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
         * Returns the OrderID for the TransactionId set to this Factory
         *
         * @return string $returnValue
         */
        private function _getOrderIdFromTransactionId()
        {
            $returnValue = null;
            $transactionId = $this->_transactionId;

            if (!empty($transactionId)) {
                $returnValue = Shopware()->Db()->fetchOne(
                    "SELECT `ordernumber` FROM `s_order` "
                    . "INNER JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id` = `s_order`.`paymentID` "
                    . "WHERE `s_order`.`transactionID`=?;",
                    array($transactionId)
                );
            }

            return $returnValue;
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
         * @return array
         */
        function _getCheckoutAddress($checkoutAddress, $type, $countryCode) {
            $address = array(
                'Type' => strtolower($type),
                'Street' => $checkoutAddress->getStreet(),
                'ZipCode' => $checkoutAddress->getZipCode(),
                'City' => $checkoutAddress->getCity(),
                'CountryCode' => $countryCode,
            );

            if ($type == 'DELIVERY') {
                $address['FirstName'] = $checkoutAddress->getFirstName();
                $address['LastName'] = $checkoutAddress->getLastName();
            }

            $company = $checkoutAddress->getCompany();
            if (!empty($company)) {
                $address['Company'] = $checkoutAddress->getCompany();
            }

            return $address;
        }

        /**
         * get profile id
         *
         * @param bool $countryCode
         * @return mixed
         */
        public function getProfileId($countryCode = false)
        {
            if (!$countryCode) {
                $countryCode = $this->_getCountryCodesByBillingAddress();
            }

            if(null !== $this->_config) {
                $profileId = $this->_config->get('RatePayProfileID' . $countryCode);
            } else{
                if (!empty($this->_transactionId)) {
                    $shopId = Shopware()->Db()->fetchOne(
                        "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
                    );
                }
                $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $countryCode, $shopId);
            }

            if ($this->_zPercent == true) {
                $profileId = $profileId . '_0RT';
            }

            return $profileId;
        }

        /**
         * get security code
         *
         * @param bool $countryCode
         * @return mixed
         */
        public function getSecurityCode($countryCode = false)
        {
            if (!$countryCode) {
                $countryCode = $this->_getCountryCodesByBillingAddress();
            }

            if(null !== $this->_config) {
                $securityCode = $this->_config->get('RatePaySecurityCode' . $countryCode);
            } else {
                if (!empty($this->_transactionId)) {
                    $shopId = Shopware()->Db()->fetchOne(
                        "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
                    );
                }
                $securityCode = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePaySecurityCode' . $countryCode, $shopId);
            }

            return $securityCode;
        }

    }