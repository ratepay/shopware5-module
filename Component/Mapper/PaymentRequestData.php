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
     * @return PaymentRequestData
     */
    public static function loadFromSession()
    {
        $method = \Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::getPaymentMethod(
            Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name']
        );

        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

        $billing = self::findAddressBilling($customer);

        $shipping = self::findAddressShipping($customer, $billing);

        $items = Shopware()->Session()->sOrderVariables['sBasket']['content'];

        $shippingCost = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'];

        $shippingTax = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax'];

        $dfpToken = Shopware()->Session()->RatePAY['dfpToken'];

        $lang = self::findLangInSession();

        $amount = self::findAmountInSession();

        return new PaymentRequestData($method, $customer, $billing, $shipping, $items, $shippingCost, $shippingTax, $dfpToken, $lang, $amount);
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

    private static function findLangInSession()
    {
        $shopContext = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $lang = $shopContext->getShop()->getLocale()->getLocale();
        $lang = substr($lang, 0, 2);
        return $lang;
    }

    private static function findAmountInSession()
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
      * @param \Shopware\Models\Customer\Customer $customer
      * @param \Shopware\Models\Customer\Address $billing
      * @return mixed
      */
    private static function findAddressShipping($customer, $billing)
    {
        if (isset(Shopware()->Session()->RatePAY['checkoutShippingAddressId']) && Shopware()->Session()->RatePAY['checkoutShippingAddressId'] > 0) {
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $checkoutAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutShippingAddressId'] ? Shopware()->Session()->RatePAY['checkoutShippingAddressId'] : Shopware()->Session()->RatePAY['checkoutBillingAddressId']));
        } else {
            $checkoutAddressShipping = $customer->getShipping() !== null ? $customer->getShipping() : $billing;
        }
        return $checkoutAddressShipping;
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     * @return mixed
     */
    private static function findAddressBilling($customer)
    {
        if (isset(Shopware()->Session()->RatePAY['checkoutBillingAddressId']) && Shopware()->Session()->RatePAY['checkoutBillingAddressId'] > 0) {
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $checkoutAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->RatePAY['checkoutBillingAddressId']));
        } else {
            $checkoutAddressBilling = $customer->getBilling();
        }

        return $checkoutAddressBilling;
    }
}
