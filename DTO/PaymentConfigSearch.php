<?php


namespace RpayRatePay\DTO;


use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;

class PaymentConfigSearch
{

    /** @var string */
    private $billingCountry;

    /** @var string */
    private $shippingCountry;

    /** @var Payment|int */
    private $paymentMethod;

    /** @var boolean */
    private $backend;

    /** @var int|Shop|\Shopware\Bundle\StoreFrontBundle\Struct\Shop */
    private $shop;

    /** @var string */
    private $currency;

    /** @var bool|null */
    private $isB2b;

    /** @var bool|null */
    private $needsAllowDifferentAddress;

    /** @var float|null */
    private $totalAmount;

    /**
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }

    /**
     * @param string $billingCountry
     */
    public function setBillingCountry($billingCountry)
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingCountry()
    {
        return $this->shippingCountry;
    }

    /**
     * @param string $shippingCountry
     */
    public function setShippingCountry($shippingCountry)
    {
        $this->shippingCountry = $shippingCountry;
        return $this;
    }

    /**
     * @return int|Payment
     */
    public function getPaymentMethod()
    {
        if(is_string($this->paymentMethod)) {
            $this->paymentMethod = Shopware()->Models()->getRepository(Payment::class)->findOneBy(['name' => $this->paymentMethod]);
        }
        return $this->paymentMethod instanceof Payment ? $this->paymentMethod->getId() : $this->paymentMethod;
    }

    /**
     * @param string|int|Payment $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBackend()
    {
        return $this->backend;
    }

    /**
     * @param bool $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
        return $this;
    }

    /**
     * @return int
     */
    public function getShop()
    {
        if(is_numeric($this->shop)) {
            $this->shop = Shopware()->Models()->find(Shop::class, $this->shop);
        }

        // parent: we do not support language shops anymore
        if ($this->shop instanceof Shop) {
            return $this->shop->getMain() ? $this->shop->getMain()->getId() : $this->shop->getId();
        }
        if ($this->shop instanceof \Shopware\Bundle\StoreFrontBundle\Struct\Shop) {
            return $this->shop->getParentId() ?: $this->shop->getId();
        }

        throw new \InvalidArgumentException('$shop must be one of: ' . Shop::class . ', ' . \Shopware\Bundle\StoreFrontBundle\Struct\Shop::class);
    }

    /**
     * @param int|\Shopware\Bundle\StoreFrontBundle\Struct\Shop|Shop $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        if(is_numeric($this->currency)) {
            $this->currency = Shopware()->Models()->find(Currency::class, $this->currency)->getCurrency();
        }
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isB2b()
    {
        return $this->isB2b;
    }

    /**
     * @param bool $isB2b
     * @return $this
     */
    public function setIsB2b($isB2b)
    {
        $this->isB2b = $isB2b;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isNeedsAllowDifferentAddress()
    {
        return $this->needsAllowDifferentAddress;
    }

    /**
     * @param bool $needsAllowDifferentAddress
     * @return $this
     */
    public function setNeedsAllowDifferentAddress($needsAllowDifferentAddress)
    {
        $this->needsAllowDifferentAddress = $needsAllowDifferentAddress;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param float $totalAmount
     * @return $this
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

}
