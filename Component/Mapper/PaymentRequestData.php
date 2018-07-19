<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 12.07.18
 * Time: 13:40
 */

namespace RpayRatePay\Component\Mapper;

use RatePAY\Service\Util;

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
     * @return strign
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

    public function __construct($method,
                                $customer,
                                $billingAddress,
                                $shippingAddress,
                                $items,
                                $shippingCost,
                                $shippingTax,
                                $dfpToken,
                                $lang,
                                $amount
    )
    {
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
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     * @param \Shopware\Models\Customer\Address $billing
     * @return string
     */
    public function getBirthday()
    {
        $dateOfBirth = '0000-00-00';
        $customerBilling = $this->customer->getBilling();

        if (Util::existsAndNotEmpty($this->customer, 'getBirthday')) {
            $dateOfBirth = $this->customer->getBirthday()->format("Y-m-d"); // From Shopware 5.2 date of birth has moved to customer object
        } else if (Util::existsAndNotEmpty($customerBilling, 'getBirthday')) {
            $dateOfBirth = $customerBilling->getBirthday()->format("Y-m-d");
        } else if (Util::existsAndNotEmpty($this->billingAddress, 'getBirthday')) {
            $dateOfBirth = $this->billingAddress->getBirthday()->format("Y-m-d");
        }

        return $dateOfBirth;
    }

    /**
     * @param mixed
     * @return string|null
     */
    public static function findCountryISO($addressObject)
    {
        $iso = null;
        if (Util::existsAndNotEmpty($addressObject, "getCountry") &&
            Util::existsAndNotEmpty($addressObject->getCountry(), "getIso")) {
            $iso = $addressObject->getCountry()->getIso();
        } elseif (Util::existsAndNotEmpty($addressObject, "getCountryId")) {
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $addressObject->getCountryId());
            $iso = $country->getIso();
        }
        return $iso;
    }
}
