<?php

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
use RpayRatePay\Enum\PaymentMethods;
use RuntimeException;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity(repositoryClass="ProfileConfigRepository")
 * @ORM\Table(name="rpay_ratepay_config")
 */
class ProfileConfig extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(name="shopId", type="integer", length=5, nullable=false)
     */
    protected $shopId;
    /**
     * @var boolean
     * @ORM\Id()
     * @ORM\Column(name="backend", type="boolean", nullable=false)
     */
    protected $backend = false;
    /**
     * @var boolean
     * @ORM\Id()
     * @ORM\Column(name="is_zero_percent_installment", type="boolean")
     */
    protected $isZeroPercentInstallment = false;
    /**
     * @var string
     * @ORM\Column(name="profileId", type="string", length=255, nullable=false)
     */
    protected $profileId;

    /**
     * @var string
     * @ORM\Column(name="security_code", type="string", length=255, nullable=false)
     */
    protected $securityCode;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_invoice_id", referencedColumnName="rpay_id")
     */
    protected $invoiceConfig;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_debit_id", referencedColumnName="rpay_id")
     */
    protected $debitConfig;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_installment_id", referencedColumnName="rpay_id")
     */
    protected $installmentConfig;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_installment0_id", referencedColumnName="rpay_id")
     */
    protected $installment0Config;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_installmentdebit_id", referencedColumnName="rpay_id")
     */
    protected $installmentDebitConfig;
    /**
     * @var ConfigPayment
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="config_prepayment_id", referencedColumnName="rpay_id")
     */
    protected $prepaymentConfig;
    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(name="country_code_billing", type="string", length=2, nullable=true)
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
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @param string $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * @return ConfigPayment
     */
    public function getInvoiceConfig()
    {
        return $this->invoiceConfig;
    }

    /**
     * @param ConfigPayment $invoiceConfig
     */
    public function setInvoiceConfig($invoiceConfig)
    {
        $this->invoiceConfig = $invoiceConfig;
    }

    /**
     * @return ConfigPayment
     */
    public function getDebitConfig()
    {
        return $this->debitConfig;
    }

    /**
     * @param ConfigPayment $debitConfig
     */
    public function setDebitConfig($debitConfig)
    {
        $this->debitConfig = $debitConfig;
    }

    /**
     * @return ConfigPayment
     */
    public function getInstallmentConfig()
    {
        return $this->installmentConfig;
    }

    /**
     * @param ConfigPayment $installmentConfig
     */
    public function setInstallmentConfig($installmentConfig)
    {
        $this->installmentConfig = $installmentConfig;
    }

    /**
     * @return ConfigPayment
     */
    public function getInstallment0Config()
    {
        return $this->installment0Config;
    }

    /**
     * @param ConfigPayment $installment0Config
     */
    public function setInstallment0Config($installment0Config)
    {
        $this->installment0Config = $installment0Config;
    }

    /**
     * @return ConfigPayment
     */
    public function getInstallmentDebitConfig()
    {
        return $this->installmentDebitConfig;
    }

    /**
     * @param ConfigPayment $installmentDebitConfig
     */
    public function setInstallmentDebitConfig($installmentDebitConfig)
    {
        $this->installmentDebitConfig = $installmentDebitConfig;
    }

    /**
     * @return ConfigPayment
     */
    public function getPrepaymentConfig()
    {
        return $this->prepaymentConfig;
    }

    /**
     * @param ConfigPayment $prepaymentConfig
     */
    public function setPrepaymentConfig($prepaymentConfig)
    {
        $this->prepaymentConfig = $prepaymentConfig;
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


    /**
     * returns the payment specific configuration
     * @param $paymentMethodName
     * @return ConfigPayment
     */
    public function getPaymentConfig($paymentMethodName)
    {
        switch ($paymentMethodName) {
            case PaymentMethods::PAYMENT_PREPAYMENT:
                return $this->prepaymentConfig;
            case PaymentMethods::PAYMENT_INSTALLMENT0:
                return $this->installment0Config;
            case PaymentMethods::PAYMENT_RATE:
                return $this->installmentConfig;
            case PaymentMethods::PAYMENT_DEBIT:
                return $this->debitConfig;
            case PaymentMethods::PAYMENT_INVOICE:
                return $this->invoiceConfig;
            default:
                throw new RuntimeException('the given payment method name does not exist: ' . $paymentMethodName);
        }
    }

    /**
     * @return bool
     */
    public function isZeroPercentInstallment()
    {
        return $this->isZeroPercentInstallment;
    }

    /**
     * @param bool $isZeroPercentInstallment
     */
    public function setZeroPercentInstallment($isZeroPercentInstallment)
    {
        $this->isZeroPercentInstallment = $isZeroPercentInstallment;
    }
}
