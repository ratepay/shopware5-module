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

        public function __construct($config = null)
        {
            $this->_config = $config;
        }

        public function setOrderId($orderId)
        {
            $this->_orderId = $orderId;
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
         * make operation
         *
         * @param string $operationType
         * @param array $operationData
         *
         * @return bool|array
         */
        public function doOperation($operationType, array $operationData) {
            $this->_logging = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();

            switch ($operationType) {
                case 'ProfileRequest':
                    return $this->makeProfileRequest($operationData);
                    break;
                case 'PaymentInit':
                    return $this->makePaymentInit();
                    break;
                case 'PaymentRequest':
                    return $this->makePaymentRequest();
                    break;
                case 'PaymentConfirm':
                    return $this->makePaymentConfirm();
                    break;
                case 'ConfirmationDeliver':
                    return $this->makeConfirmationDeliver($operationData);
                    break;
                case 'PaymentChange':
                    return $this->makePaymentChange($operationData);
                    break;
                case 'CalculationRequest':
                    return $this->makeCalculationRequest($operationData);
                    break;

            }
        }

        private function makeCalculationRequest($operationData) {
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
         * get request head
         *
         * @param bool $countryCode
         * @return \RatePAY\ModelBuilder
         */
        private function getHead($countryCode = false) {
            $systemId = $this->getSystemId();
            $boostrap = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrap();

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
                                    'Version' => Shopware()->Config()->get('version') . '_' . $boostrap->getVersion()
                                ]
                            ]
                        ]
                    ];

            if (!empty($this->_orderId)) {
                $head['External']['OrderId'] = $this->_orderId;
            }

            $mbHead = new \RatePAY\ModelBuilder('head');
            $mbHead->setArray($head);

            if (!empty($this->_transactionId)) {
                $mbHead->setTransactionId($this->_transactionId);
            }

            return $mbHead;
        }

        /**
         * make payment request
         *
         * @return mixed
         */
        private function makePaymentRequest()
        {
            $mbHead = $this->getHead();
            $mbHead->setCustomerDevice(
                $mbHead->CustomerDevice()->setDeviceToken(Shopware()->Session()->RatePAY['dfpToken'])
            );

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
                    $dateOfBirth = $shopUser->getBirthday()->format("Y-m-d");
                }
                $merchantCustomerId = $shopUser->getBilling()->getNumber();
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
                $shoppingBasket['Shipping'] = array(
                    'Description' => "Shipping costs",
                    'UnitPriceGross' => Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'],
                    'TaxRate' => Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax'],

                );
            }

            $mbContent = new \RatePAY\ModelBuilder('Content');
            $contentArr = [
                'Customer' => [
                    'Gender' => $gender,
                    'Salutation' => $salutation,
                    'FirstName' => $checkoutAddressBilling->getFirstName(),
                    'LastName' => $checkoutAddressBilling->getLastName(),
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
                    $contentArr['Payment']['DebitPayType']= 'BANK-TRANSFER';
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

            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true
            $paymentRequest = $rb->callPaymentRequest($mbHead, $mbContent);
            $this->_logging->logRequest($paymentRequest->getRequestRaw(), $paymentRequest->getResponseRaw());

            return $paymentRequest;
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
        private function makeProfileRequest($operationData)
        {
            $systemId = $this->getSystemId();
            $boostrap = new Shopware_Plugins_Frontend_RpayRatePay_Bootstrap();

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
                            'Version' => Shopware()->Config()->get('version') . '_' . $boostrap->getVersion()
                        ]
                    ]
                ]
            ]);

            $rb = new \RatePAY\RequestBuilder(true); // Sandbox mode = true

            $profileRequest = $rb->callProfileRequest($mbHead);
            $this->_logging->logRequest($profileRequest->getRequestRaw(), $profileRequest->getResponseRaw());

            if ($profileRequest->isSuccessful()) {
                return $profileRequest->getResult();
            }
            return false;
        }

        /**
         * set the sandbox mode
         *
         * @param $value
         */
        public function setSandboxMode($value)
        {
            $this->_sandboxMode = $value;
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
         * make payment init
         *
         * @return bool
         */
        private function makePaymentInit()
        {
            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true

            $paymentInit = $rb->callPaymentInit($this->getHead());
            $this->_logging->logRequest($paymentInit->getRequestRaw(), $paymentInit->getResponseRaw());

            if ($paymentInit->isSuccessful()) {
                return $paymentInit->getTransactionId();
            }
            return false;
        }

        /**
         * make payment confirm
         *
         * @return bool
         */
        private function makePaymentConfirm()
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
         * create basket array
         *
         * @param $items
         * @param $type
         * @return array
         */
        private function createBasketArray($items, $type = false) {
            $shoppingBasket = array();
            $item = array();

            foreach ($items AS $shopItem) {
                if ($shopItem->articlenumber == 'shipping') {
                    $shoppingBasket['Shipping'] = array(
                        'Description' => "Shipping costs",
                        'UnitPriceGross' => $shopItem->price,
                        'TaxRate' => $shopItem->taxRate,
                    );
                } else {
                    if (is_array($shopItem)) {
                        if ($shopItem['quantity'] == 0 && empty($type)) {
                            continue;
                        }
                        if ($shopItem['articlename'] == 'Shipping') {
                            $shoppingBasket['Shipping'] = array(
                                'Description' => "Shipping costs",
                                'UnitPriceGross' => $shopItem['priceNumeric'],
                                'TaxRate' => $shopItem['tax_rate'],
                            );
                            continue;
                        } else {
                            $item = array(
                                'Description' => $shopItem['articlename'],
                                'ArticleNumber' => $shopItem['ordernumber'],
                                'Quantity' => $shopItem['quantity'],
                                'UnitPriceGross' => $shopItem['priceNumeric'],
                                'TaxRate' => $shopItem['tax_rate'],
                            );
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
                                    $item['Quantity'] = $shopItem->returnedItems;
                                    break;
                                case 'cancellation':
                                    $item['Quantity'] = $shopItem->cancelledItems;
                            }
                        }
                    }
                    $shoppingBasket['Items'][] = array('Item' => $item);
                }
            }
            return $shoppingBasket;
        }

        /**
         * make confirmation deliver
         *
         * @param $operationData
         * @return bool
         */
        private function makeConfirmationDeliver($operationData)
        {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $operationData['orderId']);
            $countryCode = $order->getBilling()->getCountry()->getIso();
            $mbHead = $this->getHead($countryCode);

            $shoppingItems = $this->createBasketArray($operationData['items']);
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
                        'DueDate' => date('Y-m-d\Th:m:s'),
                    ]
                ];
                $mbContent->setArray($invoicing);
            }
            $rb = new \RatePAY\RequestBuilder($this->isSandboxMode()); // Sandbox mode = true
            $confirmationDeliver = $rb->callConfirmationDeliver($mbHead, $mbContent);
            $this->_logging->logRequest($confirmationDeliver->getRequestRaw(), $confirmationDeliver->getResponseRaw());

            if ($confirmationDeliver->isSuccessful()) {
                return true;
            }
            return false;
        }

        /**
         * make a payment change (return, cancellation, order change)
         *
         * @param $operationData
         * @return bool
         */
        private function makePaymentChange($operationData)
        {
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $operationData['orderId']);
            $countryCode = $order->getBilling()->getCountry()->getIso();
            $mbHead = $this->getHead($countryCode);

            if ($operationData['subtype'] == 'credit') {
                $shoppingItems = array('Discount' => $item = array(
                                        'Description' => $operationData['items']['name'],
                                        'UnitPriceGross' => $operationData['items']['price'],
                                        'TaxRate' => $operationData['items']['tax_rate']
                                    ));
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

             if (!empty($checkoutAddress->getCompany())) {
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
                $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $countryCode);
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
                $securityCode = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePaySecurityCode' . $countryCode);
            }

            return $securityCode;
        }

    }
