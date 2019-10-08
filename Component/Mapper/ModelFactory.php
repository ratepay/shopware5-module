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

}
