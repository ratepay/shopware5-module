<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 12.07.18
 * Time: 13:40
 */

namespace RpayRatePay\Component\Mapper;


class PaymentRequestData
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var Shopware\Models\Customer\Customer
     */
    private $customer;

    /**
     * @var Shopware\Models\Address\Address
     */
    private $billingAddress,

    /**
     * @var Shopware\Models\Address\Address
     */
    private $shippingAddress;

    private $items;

    private $shippingCost;

    private $shippingTax;

    private $dfpToken;

    /**
     * @return strign
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return Shopware\Models\Customer\Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return Shopware\Models\Address\Address
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return Shopware\Models\Address\Address
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

    public function __construct($method, $customer,  $billingAddress, $shippingAddress, $items, $shippingCost, $shippingTax, $dfpToken)
    {
        $this->method= $method;
        $this->customer = $customer;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->items = $items;
        $this->shippingCost = $shippingCost;
        $this->shippingTax = $shippingTax;
        $this->dfpToken = $dfpToken
    }

    /**
     * @param Shopware\Models\Customer\Customer $customer
     * @param Shopware\Models\Address\Address $billing
     * @return string
     */
    public function getBirthday()
    {
        $dateOfBirth = '0000-00-00';
        $customerBilling = $this->customer->getBilling();

        if ($this->existsAndNotEmpty($this->customer, 'getBirthday')) {
            $dateOfBirth = $this->customer->getBirthday()->format("Y-m-d"); // From Shopware 5.2 date of birth has moved to customer object
        } else if ($this->existsAndNotEmpty($customerBilling, 'getBirthday')) {
            $dateOfBirth = $customerBilling->getBirthday()->format("Y-m-d");
        } else if ($this->existsAndNotEmpty($this->billing, 'getBirthday')) {
            $dateOfBirth = $this->billing->getBirthday()->format("Y-m-d");
        }

        return $dateOfBirth;
    }

    /**
     * @return PaymentRequestData
     */
    public static function loadFromSession()
    {
        $method = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::getPaymentMethod(
            Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name']
        );

        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

        $billing = self::findAddressBilling($customer);

        $shipping = self::findAddressShipping($customer, $billing);

        $items = Shopware()->Session()->sOrderVariables['sBasket']['content'];

        $shippingCost = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'];

        $shippingTax = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsTax'];

        $dfpToken = Shopware()->Session()->RatePAY['dfpToken'];

        return new PaymentRequestData($method, $customer, $billing, $shipping, $items, $shippingCost, $shippingTax, $dfpToken);
    }

     /**
      * @param Shopware\Models\Customer\Customer $customer
      * @param Shopware\Models\Address\Address $billing
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
     * @param Shopware\Models\Customer\Customer $customer
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
