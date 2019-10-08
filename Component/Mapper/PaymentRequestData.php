<?php
namespace RpayRatePay\Component\Mapper;

use DateTime;
use RpayRatePay\DTO\BankData;
use RpayRatePay\DTO\InstallmentDetails;
use RpayRatePay\Enum\PaymentMethods;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

class PaymentRequestData
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Address
     */
    private $billingAddress;

    /**
     * @var Address
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
     * @var BankData
     */
    private $bankData;
    /**
     * @var InstallmentDetails
     */
    private $installmentDetails;
    /**
     * @var boolean
     */
    private $itemsAreInNet;
    /**
     * @var Shop
     */
    private $shop;

    public function __construct(
        Payment $paymentMethod,
        Customer $customer,
        Address $billingAddress,
        Address $shippingAddress,
        $items,
        $shippingCost,
        $shippingTax,
        $dfpToken,
        Shop $shop,
        $amount,
        $currencyId,
        $itemsAreInNet,
        BankData $bankData = null,
        InstallmentDetails $installmentDetails = null
    ) {
        $this->method = $paymentMethod;
        $this->customer = $customer;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->items = $items;
        $this->shippingCost = $shippingCost;
        $this->shippingTax = $shippingTax;
        $this->dfpToken = $dfpToken;
        $this->shop = $shop;
        $this->lang = substr($shop->getLocale()->getLocale(), 0, 2);
        $this->amount = $amount;
        $this->currencyId = $currencyId;
        $this->itemsAreInNet = $itemsAreInNet;
        $this->bankData = $bankData;
        $this->installmentDetails = $installmentDetails;
    }

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
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return Address
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return Address
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

    /**
     * @return BankData
     */
    public function getBankData()
    {
        return $this->bankData;
    }

    /**
     * @param BankData $bankData
     */
    public function setBankData($bankData)
    {
        $this->bankData = $bankData;
    }

    /**
     * @return InstallmentDetails
     */
    public function getInstallmentDetails()
    {
        return $this->installmentDetails;
    }

    /**
     * @param InstallmentDetails $installmentDetails
     */
    public function setInstallmentDetails($installmentDetails)
    {
        $this->installmentDetails = $installmentDetails;
    }

    /**
     * @return bool
     */
    public function isItemsAreInNet()
    {
        return $this->itemsAreInNet;
    }

    /**
     * @param bool $itemsAreInNet
     */
    public function setItemsAreInNet($itemsAreInNet)
    {
        $this->itemsAreInNet = $itemsAreInNet;
    }

    /**
     * @return Shop
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @param Shop $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }


    /**
     * @return string
     */
    public function getBirthday()
    {
        /** @var DateTime $birthday */
        $birthday = $this->customer->getBirthday();
        return $birthday ? $birthday->format('Y-m-d') : '0000-00-00'; //TODO default birthday configuration?
    }
}
