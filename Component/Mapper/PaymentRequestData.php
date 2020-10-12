<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\Mapper;

use RatePAY\Service\Math;
use RatePAY\Service\Util;
use RpayRatePay\Component\Model\ShopwareAddressWrapper;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ShopwareUtil;

class PaymentRequestData
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var \Shopware\Models\Customer\Customer
     */
    private $customer;

    /**
     * @var mixed
     */
    private $billingAddress;

    /**
     * @var mixed
     */
    private $shippingAddress;

    private $items;

    private $shippingCost;

    private $shippingTax;

    private $dfpToken;

    private $lang;

    private $amount;

    /** @var int */
    private $currencyId;

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return \Shopware\Models\Customer\Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return mixed
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return float
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * @return float
     */
    public function getShippingTax()
    {
        return $this->shippingTax;
    }

    /**
     * @return mixed
     */
    public function getDfpToken()
    {
        return $this->dfpToken;
    }

    /**
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    public function __construct(
        $method,
        $customer,
        $billingAddress,
        $shippingAddress,
        $items,
        $shippingCost,
        $shippingTax,
        $dfpToken,
        $lang,
        $amount,
        $currencyId
    ) {
        $this->method = $method;
        $this->customer = $customer;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->items = $items;
        $this->shippingCost = $shippingCost;
        $this->shippingTax = $shippingTax;
        $this->dfpToken = $dfpToken;
        $this->lang = $lang;
        $this->amount = $amount;
        $this->currencyId = $currencyId;
    }

    /**
     * @return string
     */
    public function getBirthday()
    {
        $dateOfBirth = '0000-00-00';

        $customerWrapped = new ShopwareCustomerWrapper($this->customer, Shopware()->Models());
        $customerBilling = $customerWrapped->getBilling();

        if (Util::existsAndNotEmpty($this->customer, 'getBirthday')) {
            $dateOfBirth = $this->customer->getBirthday()->format('Y-m-d'); // From Shopware 5.2 date of birth has moved to customer object
        } elseif (Util::existsAndNotEmpty($customerBilling, 'getBirthday')) {
            $dateOfBirth = $customerBilling->getBirthday()->format('Y-m-d');
        } elseif (Util::existsAndNotEmpty($this->billingAddress, 'getBirthday')) {
            $dateOfBirth = $this->billingAddress->getBirthday()->format('Y-m-d');
        }

        return $dateOfBirth;
    }

    /**
     * @param $addressObject
     * @return string
     * @throws \Exception
     */
    public static function findCountryISO($addressObject)
    {
        $addressWrapped = new ShopwareAddressWrapper($addressObject, Shopware()->Models());
        $iso = $addressWrapped->getCountry()->getIso();
        return $iso;
    }
}
