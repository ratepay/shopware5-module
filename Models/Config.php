<?php

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_config")
 */
class Config extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(name="shopId", type="integer", length=5, nullable=false)
     */
    protected $shopId;
    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(name="country", type="string", length=30, nullable=false)
     */
    protected $country;
    /**
     * @var boolean
     * @ORM\Id()
     * @ORM\Column(name="backend", type="boolean", nullable=false)
     */
    protected $backend = false;
    /**
     * @var string
     * @ORM\Column(name="profileId", type="string", length=255, nullable=false)
     */
    protected $profileId;
    /**
     * @var int
     * @ORM\Column(name="invoice", type="integer", length=2, nullable=false)
     */
    protected $invoice;
    /**
     * @var int
     * @ORM\Column(name="debit", type="integer", length=2, nullable=false)
     */
    protected $debit;
    /**
     * @var int
     * @ORM\Column(name="installment", type="integer", length=2, nullable=false)
     */
    protected $installment;
    /**
     * @var int
     * @ORM\Column(name="installment0", type="integer", length=2, nullable=false)
     */
    protected $installment0;
    /**
     * @var int
     * @ORM\Column(name="installmentDebit", type="integer", length=2, nullable=false)
     */
    protected $installmentDebit;
    /**
     * @var int
     * @ORM\Column(name="prepayment", type="integer", length=2, nullable=false)
     */
    protected $prepayment;
    /**
     * @var string
     * @ORM\Column(name="device_fingerprint_status", type="string", length=3, nullable=false)
     */
    protected $deviceFingerprintStatus;
    /**
     * @var string
     * @ORM\Column(name="device_fingerprint_snippet_id", type="string", length=55, nullable=true)
     */
    protected $deviceFingerprintSnippetId;
    /**
     * @var string
     * @ORM\Column(name="country_code_billing", type="string", length=30, nullable=true)
     */
    protected $countryCodeBilling;
    /**
     * @var string
     * @ORM\Column(name="country_code_delivery", type="string", length=30, nullable=true)
     */
    protected $countryCodeDelivery;
    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=30, nullable=true)
     */
    protected $currency;
    /**
     * @var string
     * @ORM\Column(name="error_default", type="text", length=30, nullable=true, options={"default":"Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href='http://www.ratepay.com/legal' target='_blank'>RatePAY-Datenschutzerklärung</a>"})
     */
    protected $errorDefault;

    /**
     * @var boolean
     * @ORM\Column(name="sandbox", type="boolean")
     */
    protected $sandbox = false;

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param int $shopId
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * @return int
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param int $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
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
    }

    /**
     * @return string
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param string $profileId
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }

    /**
     * @return int
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param int $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return int
     */
    public function getDebit()
    {
        return $this->debit;
    }

    /**
     * @param int $debit
     */
    public function setDebit($debit)
    {
        $this->debit = $debit;
    }

    /**
     * @return int
     */
    public function getInstallment()
    {
        return $this->installment;
    }

    /**
     * @param int $installment
     */
    public function setInstallment($installment)
    {
        $this->installment = $installment;
    }

    /**
     * @return int
     */
    public function getInstallment0()
    {
        return $this->installment0;
    }

    /**
     * @param int $installment0
     */
    public function setInstallment0($installment0)
    {
        $this->installment0 = $installment0;
    }

    /**
     * @return int
     */
    public function getInstallmentDebit()
    {
        return $this->installmentDebit;
    }

    /**
     * @param int $installmentDebit
     */
    public function setInstallmentDebit($installmentDebit)
    {
        $this->installmentDebit = $installmentDebit;
    }

    /**
     * @return int
     */
    public function getPrepayment()
    {
        return $this->prepayment;
    }

    /**
     * @param int $prepayment
     */
    public function setPrepayment($prepayment)
    {
        $this->prepayment = $prepayment;
    }

    /**
     * @return string
     */
    public function getDeviceFingerprintStatus()
    {
        return $this->deviceFingerprintStatus;
    }

    /**
     * @param string $deviceFingerprintStatus
     */
    public function setDeviceFingerprintStatus($deviceFingerprintStatus)
    {
        $this->deviceFingerprintStatus = $deviceFingerprintStatus;
    }

    /**
     * @return string
     */
    public function getDeviceFingerprintSnippetId()
    {
        return $this->deviceFingerprintSnippetId;
    }

    /**
     * @param string $deviceFingerprintSnippetId
     */
    public function setDeviceFingerprintSnippetId($deviceFingerprintSnippetId)
    {
        $this->deviceFingerprintSnippetId = $deviceFingerprintSnippetId;
    }

    /**
     * @return string
     */
    public function getCountryCodeBilling()
    {
        return $this->countryCodeBilling;
    }

    /**
     * @param string $countryCodeBilling
     */
    public function setCountryCodeBilling($countryCodeBilling)
    {
        $this->countryCodeBilling = $countryCodeBilling;
    }

    /**
     * @return string
     */
    public function getCountryCodeDelivery()
    {
        return $this->countryCodeDelivery;
    }

    /**
     * @param string $countryCodeDelivery
     */
    public function setCountryCodeDelivery($countryCodeDelivery)
    {
        $this->countryCodeDelivery = $countryCodeDelivery;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getErrorDefault()
    {
        return $this->errorDefault;
    }

    /**
     * @param string $errorDefault
     */
    public function setErrorDefault($errorDefault)
    {
        $this->errorDefault = $errorDefault;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->sandbox;
    }

    /**
     * @param bool $sandbox
     */
    public function setSandbox($sandbox)
    {
        $this->sandbox = $sandbox;
    }
}