<?php

use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Mapper\BankData;
use RatePAY\Service\Util;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\SessionLoader;

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

    private $backend;

    private $netItemPrices;

    public function __construct($config = null, $backend = false, $netItemPrices = false)
    {
        $this->_logging = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();
        $this->_config = $config;
        $this->backend = $backend;
        $this->netItemPrices = $netItemPrices;
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
     * TODO: remove... use code in PaymentRequestData
     * @depcrecated
     *
     * @return string
     */
    private function _getCountryCodesByBillingAddress()
    {
        // Checkout address ids are set from shopware version >=5.2.0
        if (isset($this->getSession()->checkoutBillingAddressId) && $this->getSession()->checkoutBillingAddressId > 0) {
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $checkoutAddressBilling = $addressModel->findOneBy(array('id' => $this->getSession()->checkoutBillingAddressId));
            return $checkoutAddressBilling->getCountry()->getIso();
        } else {
            $shopUser = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $this->getSession()->sUserId);
            $userWrapped = new ShopwareCustomerWrapper($shopUser, Shopware()->Models());
            $country = $userWrapped->getBillingCountry();
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
     * @deprecated
     * @return bool|array|object
     */
    public function callRequest($operationType, array $operationData = []) {
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

    public function callCalculationRequest($operationData) {
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
    public function callPaymentConfirm($countryCode = false)
    {
        $mbHead = $this->getHead($countryCode);
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
     * @throws \RatePAY\Exception\ModelException
     */
    private function getHead($countryCode = false) {
            $systemId = $this->getSystemId();
            $bootstrap = new \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap('ratepay_config');

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

        //side effect
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
     * @param null|RpayRatePay\Component\Mapper\PaymentRequestData $paymentRequestData
     * @param null|RpayRatePay\Component\Mapper\BankData $bankData
     *
     * @return \RatePAY\RequestBuilder
     * @throws \RatePAY\Exception\ModelException
     * @throws \Exception
     */
    public function callPaymentRequest($paymentRequestData = null, $bankData = null)
    {
        $sessionLoader = new SessionLoader($this->backend ?
            Shopware()->BackendSession() :
            $this->getSession()
        );

        if (is_null($paymentRequestData)) {
            $paymentRequestData = $sessionLoader->getPaymentRequestData();
        }

        $method = $paymentRequestData->getMethod();

        if ($method == 'INSTALLMENT0') {
            $this->setZPercent(); //side effect
            $method = 'INSTALLMENT'; //state
        }

        $mbHead = $this->getHead(PaymentRequestData::findCountryISO($paymentRequestData->getBillingAddress()));

        $mbHead->setCustomerDevice(
            $mbHead->CustomerDevice()->setDeviceToken($paymentRequestData->getDfpToken())
        );

        $customer = $paymentRequestData->getCustomer();

        $checkoutAddressBilling = $paymentRequestData->getBillingAddress();
        $checkoutAddressShipping = $paymentRequestData->getShippingAddress();
        $company = $checkoutAddressBilling->getCompany();

        if(empty($company)) {
            $dateOfBirth = $paymentRequestData->getBirthday();
        }

        if (Util::existsAndNotEmpty($customer, "getNumber")) { // From Shopware 5.2 billing number has moved to customer object
            $merchantCustomerId = $customer->getNumber();
        } elseif (Util::existsAndNotEmpty($checkoutAddressBilling, "getNumber")) {
            $merchantCustomerId = $checkoutAddressBilling->getNumber();
        }

        $countryCodeBilling = PaymentRequestData::findCountryISO($checkoutAddressBilling);
        $countryCodeShipping = PaymentRequestData::findCountryISO($checkoutAddressShipping);

        if(is_null($countryCodeBilling || is_null($countryCodeShipping))) {
            Shopware()->Pluginlogger()->error('Country code not loaded....');
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

        $shopItems = $paymentRequestData->getItems();

        $shoppingBasket = $this->createBasketArray($shopItems);

        if ($paymentRequestData->getShippingCost() > 0) {
            // As we know this is the first request, we do not pass unsaved `orderId`
            $useFallbackShipping = $this->usesShippingItemFallback(/*$this->_getOrderIdFromTransactionId()*/);
            $shippingItem = $this->getShippingItemData(
                $paymentRequestData,
                $useFallbackShipping
            );

            if ($useFallbackShipping) {
                $shoppingBasket['Items'][] = ['Item' => $shippingItem];
            } else {
                $shoppingBasket['Shipping'] = $shippingItem;
            }
        }

        $lang = $paymentRequestData->getLang();

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
                    'Email' => $customer->getEmail(),
                    'Phone' => [
                        'DirectDial' => $checkoutAddressBilling->getPhone()
                    ],
                ],
            ],
            'ShoppingBasket' => $shoppingBasket,
            'Payment' => [
                'Method' => strtolower($method),
                'Amount' => $paymentRequestData->getAmount()
            ]
        ];

        if (!empty($company)) {
            $contentArr['Customer']['CompanyName'] = $checkoutAddressBilling->getCompany();
            $contentArr['Customer']['VatId'] = $checkoutAddressBilling->getVatId();
        }
        $elv = false;
        if (!empty($installmentDetails)) {
            $serviceUtil = new Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util();

            $contentArr['Payment']['DebitPayType'] = $serviceUtil->getDebitPayType($this->getSession()->RatePAY['ratenrechner']['payment_firstday']
                );

            if ($contentArr['Payment']['DebitPayType'] == 'DIRECT-DEBIT') {
                $elv = true;
            }

            $calculatorAmountWithoutInterest = $this->getSession()->RatePAY['ratenrechner']['amount'];

            if ((string)$calculatorAmountWithoutInterest !==  (string)$paymentRequestData->getAmount()) {
                throw new \Exception('Attempt to create order with wrong amount in installment calculator.' .
                    'Expected '.  $paymentRequestData->getAmount() . " Got " . $calculatorAmountWithoutInterest
                );
            }

            $contentArr['Payment']['Amount'] = $this->getSession()->RatePAY['ratenrechner']['total_amount'];
            $contentArr['Payment']['InstallmentDetails'] = $installmentDetails;
        }

        if ($method === 'ELV' || ($method == 'INSTALLMENT' && $elv == true)) {
            if (is_null($bankData)) {
                $bankData = $sessionLoader->getBankData($checkoutAddressBilling, $customer->getId());
            }
            $contentArr['Customer']['BankAccount'] = $bankData->toArray();
        }

        $mbContent->setArray($contentArr);

        $rb = new \RatePAY\RequestBuilder($this->isSandboxMode());
        $paymentRequest = $rb->callPaymentRequest($mbHead, $mbContent);
        $this->_logging->logRequest($paymentRequest->getRequestRaw(), $paymentRequest->getResponseRaw());

        /*Shopware()->Pluginlogger()->info("REQUEST");
        Shopware()->Pluginlogger()->info($paymentRequest->getRequestRaw());
        Shopware()->Pluginlogger()->info("RESPONSE");
        Shopware()->Pluginlogger()->info($paymentRequest->getResponseRaw());*/
        return $paymentRequest;
    }

    private function getSession()
    {
        $sess = $this->backend ? Shopware()->BackendSession() : Shopware()->Session();
        return $sess;
    }

    /**
     * get payment details
     *
     * @return array
     */
    private function getPaymentDetails() {
        $paymentDetails = array();

        $paymentDetails['InstallmentNumber'] = $this->getSession()->RatePAY['ratenrechner']['number_of_rates'];
        $paymentDetails['InstallmentAmount'] = $this->getSession()->RatePAY['ratenrechner']['rate'];
        $paymentDetails['LastInstallmentAmount'] = $this->getSession()->RatePAY['ratenrechner']['last_rate'];
        $paymentDetails['InterestRate'] = $this->getSession()->RatePAY['ratenrechner']['interest_rate'];
        $paymentDetails['PaymentFirstday'] = $this->getSession()->RatePAY['ratenrechner']['payment_firstday'];

        return $paymentDetails;
    }

        /**
         * @param $operationData
         * @return bool|array
         * @throws \RatePAY\Exception\ModelException
         */
        private function callProfileRequest($operationData)
        {
            $systemId = $this->getSystemId();
            $sandbox = true;
            $bootstrap = new \Shopware_Plugins_Frontend_RpayRatePay_Bootstrap('ratepay_config');

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
     * @param bool$type* @param null $orderId
     * @return array
     */
    private function createBasketArray($items, $type = false, $orderId = null) {

        $useFallbackShipping = $this->usesShippingItemFallback($orderId);
        $basketFactory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_BasketArrayBuilder(
            $this->_retry ,
            $type,
            $this->netItemPrices,
            $useFallbackShipping
        );

        foreach ($items as $shopItem) {
            $basketFactory->addItem($shopItem);
        }

        return $basketFactory->toArray();
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

        $shoppingItems = $this->createBasketArray($operationData['items'], $type, $operationData['orderId']);
        $shoppingBasket = [
            'ShoppingBasket' => $shoppingItems,
        ];

        $mbContent = new \RatePAY\ModelBuilder('Content');
        $mbContent->setArray($shoppingBasket);

        $documentModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document');
        $document = $documentModel->findOneBy(array('orderId' => $operationData['orderId'], 'type' => 1));

        if (!is_null($document)) {
            $dateObject = new \DateTime();
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
            $shoppingItems = $this->createBasketArray($operationData['items'], $operationData['subtype'], $operationData['orderId']);
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
            if (!empty($this->_transactionId)) {
                $customerIp = Shopware()->Db()->fetchOne(
                    "SELECT `remote_addr` FROM `s_order` WHERE `transactionID`=" . $this->_transactionId
                );
            } else {
                $customerIp = $_SERVER['SERVER_ADDR'];
            }
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
    private function _getCheckoutAddress($checkoutAddress, $type, $countryCode) {
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
     * TODO: USE ConfigLoader->getProfileId
     */
    public function getProfileId($countryCode = false)
    {

        if (!$countryCode) {
            $countryCode = $this->_getCountryCodesByBillingAddress();
        }

        $configKey = RpayRatePay\Component\Service\ConfigLoader::getProfileIdKey($countryCode, $this->backend);
        if(null !== $this->_config) {
            $profileId = $this->_config->get($configKey);
        } else{
            if (!empty($this->_transactionId)) {
                $shopId = Shopware()->Db()->fetchOne(
                    "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
                );
            }
            $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get($configKey, $shopId);
        }

        if ($this->_zPercent == true) {
            $profileId = $profileId . '_0RT';
        }

        if (empty($profileId)) {
            throw new \Exception("Profile id not found: key " . $configKey);
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
        $configKey = RpayRatePay\Component\Service\ConfigLoader::getSecurityCodeKey($countryCode, $this->backend);

        if(null !== $this->_config) {
            $securityCode = $this->_config->get($configKey);
        } else {
            if (!empty($this->_transactionId)) {
                $shopId = Shopware()->Db()->fetchOne(
                    "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
                );
            }
            $securityCode = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get($configKey);
        }

        if (empty($securityCode)) {
            throw new \Exception("Security code not found: key " . $configKey);
        }

        return $securityCode;
    }

    /**
     * @param array $shippingData
     * @param bool $useFallbackShipping
     * @return array
     */
    private function getShippingItemData(PaymentRequestData $shippingData, $useFallbackShipping = false)
    {
        $item = [
            'Description' => 'Shipping costs',
            'UnitPriceGross' => $shippingData->getShippingCost(),
            'TaxRate' => $shippingData->getShippingTax(),
        ];

        if ($useFallbackShipping) {
            $item['ArticleNumber'] = 'shipping';
            $item['Quantity'] = 1;
            $item['Description'] = 'shipping';
        }

        $system = Shopware()->System();
        $userGroup = Shopware()->Db()->fetchRow('
            SELECT * FROM s_core_customergroups WHERE groupkey = ?
        ', [$system->sUSERGROUP]);

        if ($userGroup['tax'] == 0) {
            $cost = $item['UnitPriceGross'];
            $tax = $item['TaxRate'];
            $item['UnitPriceGross'] = number_format($cost / 100 * $tax +  $cost , 2);
        }

        return $item;
    }

    /**
     * @param null $orderId
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    private function usesShippingItemFallback($orderId = null)
    {
        $configured = !!(Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()
            ->get('RatePayUseFallbackShippingItem'));

        if (!$orderId) {
            return $configured;
//                return boolval($result['ratepay_fallback_shipping']);
        }

        $query = "SELECT ratepay_fallback_shipping FROM `s_order_attributes` WHERE orderID = ?";
        $result = Shopware()->Db()->executeQuery($query, [$orderId])->fetch()["ratepay_fallback_shipping"];

        return is_null($result) ? false : (boolval($result) || $configured);
    }
}