<?php
namespace RpayRatePay\Component\Mapper;
use Monolog\Logger;
use RatePAY\ModelBuilder;
use RatePAY\RequestBuilder;
use RatePAY\Service\Math;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Mapper\BankData;
use RatePAY\Service\Util;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Component\Service\ShopwareUtil;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Logger\RequestLogger;

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
class ModelFactory
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

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;
    /**
     * @var object|ConfigService
     */
    protected $configService;

    /**
     * @var Logger
     */
    protected $logger;


    public function __construct($config = null, $backend = false, $netItemPrices = false)
    {
        $this->_logging = Shopware()->Container()->get(RequestLogger::class);
        $this->_config = $config;
        $this->backend = $backend;
        $this->netItemPrices = $netItemPrices;
        $this->db = Shopware()->Container()->get('db');
        $this->configService = Shopware()->Container()->get(ConfigService::class);
        $this->logger = Shopware()->Container()->get('rpay_rate_pay.logger');
    }

    public function setOrderId($orderId)
    {
        $this->_orderId = $orderId;
    }

    public function setZPercent()
    {
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
            $checkoutAddressBilling = $addressModel->findOneBy(['id' => $this->getSession()->checkoutBillingAddressId]);
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

    public function callCalculationRequest($operationData)
    {
        $mbHead = $this->getHead();
        $mbContent = new ModelBuilder('Content');

        $calcArray['Amount'] = $operationData['payment']['amount'];
        $calcArray['PaymentFirstday'] = $operationData['payment']['paymentFirstday'];
        if ($operationData['subtype'] == 'calculation-by-time') {
            $calcArray['CalculationTime']['Month'] = $operationData['payment']['month'];
        } else {
            $calcArray['CalculationRate']['Rate'] = $operationData['payment']['rate'];
        }

        $mbContent->setArray(['InstallmentCalculation' => $calcArray]);
        $rb = new RequestBuilder($this->isSandboxMode());

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
    private function getHead($countryCode = false)
    {
        $systemId = $this->getSystemId();


        $version = '6.0'; //TODO read plugin version info

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
                        'Version' => Shopware()->Config()->get('version') . '/' . $version
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
    public function getSandboxMode($countryCode)
    {
        $profileId = $this->getProfileId($countryCode);
        if (strstr($profileId, '_0RT') !== false) {
            $profileId = substr($profileId, 0, -4);
        }

        $qry = 'SELECT sandbox FROM rpay_ratepay_config WHERE profileId = "' . $profileId . '"';
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
        //TODO move to service
    }

    private function getSession()
    {
        $sess = $this->backend ? Shopware()->BackendSession() : Shopware()->Session();
        return $sess;
    }

    /**
     * @param $operationData
     * @return bool|array
     * @throws \RatePAY\Exception\ModelException
     */
    public function callProfileRequest($operationData)
    {
        $systemId = $this->getSystemId();
        $sandbox = true;
        $version = '6.0'; //TODO read plugin version info

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
                        'Version' => Shopware()->Config()->get('version') . '/' . $version
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
            return ['result' => $profileRequest->getResult(), 'sandbox' => $sandbox];
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
        $systemId = Shopware()->Db()->fetchOne('SELECT `host` FROM `s_core_shops` WHERE `default`=1') ?: $_SERVER['SERVER_ADDR'];

        return $systemId;
    }

    /**
     * create basket array
     * @param $currency
     * @param $items
     * @param bool$type* @param null $orderId
     * @return array
     */
    private function createBasketArray($currency, $items, $type = false, $orderId = null)
    {
        $useFallbackShipping = $this->usesShippingItemFallback($orderId);
        $useFallbackDiscount = $this->usesDiscountItemFallback($orderId);
        $basketFactory = new BasketArrayBuilder(
            $this->_retry,
            $type,
            $this->netItemPrices,
            $useFallbackShipping,
            $useFallbackDiscount
        );

        foreach ($items as $shopItem) {
            $basketFactory->addItem($shopItem);
        }

        $array = $basketFactory->toArray();

        $currencyRepo = Shopware()->Models()->getRepository(\Shopware\Models\Shop\Currency::class);
        if($currency instanceof \Shopware\Models\Shop\Currency) {
            // nothing to do
        } else if(is_numeric($currency)) { //currency is the id
            /** @var \Shopware\Models\Shop\Currency $currencyEntity */
            $currency = $currencyRepo->find($currency);
        } else if(is_string($currency)) { //currency is NOT the iso code
            /** @var \Shopware\Models\Shop\Currency $currencyEntity */
            $currency = $currencyRepo->findOneBy(['currency' => $currency]);
        } else {
            $currency = "EUR"; //fallback
        }

        $currency = $currency instanceof \Shopware\Models\Shop\Currency ? $currency->getCurrency() : $currency;

        if($currency) {
            $array['Currency'] = $currency;
        }

        return $array;
    }

    /**
     * call confirmation deliver
     *
     * @param $operationData
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \RatePAY\Exception\ModelException
     */
    public function callConfirmationDeliver($operationData)
    {
        /** @var \Shopware\Models\Order\Order $order */
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

        $shoppingItems = $this->createBasketArray($order->getCurrency(), $operationData['items'], $type, $operationData['orderId']);
        $shoppingBasket = [
            'ShoppingBasket' => $shoppingItems,
        ];

        $mbContent = new \RatePAY\ModelBuilder('Content');
        $mbContent->setArray($shoppingBasket);

        $documentModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document');
        $document = $documentModel->findOneBy(['orderId' => $operationData['orderId'], 'type' => 1]);

        if (!is_null($document)) {
            $dateObject = new \DateTime();
            $currentDate = $dateObject->format('Y-m-d');
            $currentTime = $dateObject->format('H:m:s');
            $currentDateTime = $currentDate . 'T' . $currentTime;

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
            return $this->callConfirmationDeliver($operationData);
        }

        return false;
    }

    /**
     * call a payment change (return, cancellation, order change)
     *
     * @param $operationData
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \RatePAY\Exception\ModelException
     */
    public function callPaymentChange($operationData)
    {
        /** @var \Shopware\Models\Order\Order $order */
        $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $operationData['orderId']);
        $countryCode = $order->getBilling()->getCountry()->getIso();
        $method = $order->getPayment()->getName();

        if ($method == 'rpayratepayrate0') {
            $this->setZPercent();
        }

        $mbHead = $this->getHead($countryCode);

        if ($operationData['subtype'] == 'credit') {
            if ($operationData['items']['price'] > 0) {
                $shoppingItems['Items'] = ['Item' => $item = [
                    'ArticleNumber' => $operationData['items']['articleordernumber'],
                    'Quantity' => 1,
                    'Description' => $operationData['items']['name'],
                    'UnitPriceGross' => $operationData['items']['price'],
                    'TaxRate' => $operationData['items']['tax_rate'],
                ]];
            } else {
                $shoppingItems = ['Discount' => $item = [
                    'Description' => $operationData['items']['name'],
                    'UnitPriceGross' => $operationData['items']['price'],
                    'TaxRate' => $operationData['items']['tax_rate']
                ]];
            }
            $shoppingItems['Currency'] = $order->getCurrency();
        } else {
            $shoppingItems = $this->createBasketArray($order->getCurrency(), $operationData['items'], $operationData['subtype'], $operationData['orderId']);
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
            return $this->callPaymentChange($operationData);
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
                'SELECT `ordernumber` FROM `s_order` '
                . 'INNER JOIN `s_core_paymentmeans` ON `s_core_paymentmeans`.`id` = `s_order`.`paymentID` '
                . 'WHERE `s_order`.`transactionID`=?;',
                [$transactionId]
            );
        }

        return $returnValue;
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

        $shopId = null;
        if (!empty($this->_transactionId)) {
            $shopId = Shopware()->Db()->fetchOne(
                "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
            );
        }
        return $this->configService->getProfileId($countryCode, $shopId, $this->_zPercent, $this->backend);
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

        $shopId = null;
        if (!empty($this->_transactionId)) {
            $shopId = Shopware()->Db()->fetchOne(
                "SELECT `subshopID` FROM `s_order` WHERE `transactionID`= '" . $this->_transactionId . "'"
            );
        }
        return $this->configService->getSecurityCode($countryCode, $shopId, $this->backend);
    }

    /**
     * @param array $shippingData
     * @param bool $useFallbackShipping
     * @return array
     */
    private function getShippingItemData(PaymentRequestData $shippingData, $useFallbackShipping = false)
    {
        $priceGross = $this->netItemPrices ?
            Math::netToGross($shippingData->getShippingCost(), $shippingData->getShippingTax()) :
            $shippingData->getShippingCost();

        $priceGross = round($priceGross, 3);

        $item = [
            'Description' => 'Shipping costs',
            'UnitPriceGross' => $priceGross,
            'TaxRate' => $shippingData->getShippingTax(),
        ];

        if ($useFallbackShipping) {
            $item['ArticleNumber'] = 'shipping';
            $item['Quantity'] = 1;
            $item['Description'] = 'shipping';
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
        $default = $this->configService->isCommitShippingAsCartItem();

        if (!$orderId) {
            return $default;
        }

        $query = 'SELECT ratepay_fallback_shipping FROM `s_order_attributes` WHERE orderID = ?';
        $result = $this->db->executeQuery($query, [$orderId])->fetch()['ratepay_fallback_shipping'];

        return is_null($result) ? false : (boolval($result) || $default);
    }

    protected function usesDiscountItemFallback($orderId = null)
    {
        $default = $this->configService->isCommitDiscountAsCartItem();

        if (!$orderId) {
            return $default;
        }

        $query = 'SELECT ratepay_fallback_discount FROM `s_order_attributes` WHERE orderID = ?';
        $result = $this->db->executeQuery($query, [$orderId])->fetchColumn('ratepay_fallback_discount');

        return is_null($result) ? false : (boolval($result) || $default);
    }
}
