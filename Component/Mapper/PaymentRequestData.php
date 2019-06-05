<?php

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
                                $amount
    ) {
        $this->method = $method;
        $this->customer = $customer;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->items = $items;

        // Shopware does have a bug - so the tax_rate might be the wrong value.
        // Issue: https://issues.shopware.com/issues/SW-24119
        foreach($this->items as $i=>$item) {
            if(ShopwareUtil::assertMinimumShopwareVersion('5.5.0')) {
                //$this->items[$i]['tax_rate'] = $item['taxID'] == 0 ? 0 : $item['tax_rate'];
                $amountNet = $item['amountnetNumeric'];
                $amountGross = $item['amountNumeric'];
            } else {
                $amountNet = floatval(str_replace(',', '.', $item['amountnet']));
                $amountGross = floatval(str_replace(',', '.', $item['amount']));
            }
            $calculatedTaxRate = Math::taxFromPrices($amountNet, $amountGross);
            $this->items[$i]['tax_rate'] = round($calculatedTaxRate, 2);
        }

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
