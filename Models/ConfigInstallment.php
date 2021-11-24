<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
use RpayRatePay\Enum\PaymentFirstDay;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity(repositoryClass="ConfigInstallmentRepository")
 * @ORM\Table(name="ratepay_profile_config_method_installment")
 */
class ConfigInstallment extends ModelEntity
{
    /**
     * @var ConfigPayment
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $paymentConfig;

    /**
     * @var array
     * @ORM\Column(name="month_allowed", type="simple_array", nullable=false)
     */
    protected $monthsAllowed;

    /**
     * @var boolean
     * @ORM\Column(name="is_banktransfer_allowed", type="boolean", nullable=false)
     */
    protected $bankTransferAllowed;

    /**
     * @var boolean
     * @ORM\Column(name="is_debit_allowed", type="boolean", nullable=false)
     */
    protected $debitAllowed;

    /**
     * @var string
     * @ORM\Column(name="default_payment_type", type="string", nullable=false)
     */
    protected $defaultPaymentType;

    /**
     * @var float
     * @ORM\Column(name="rate_min_normal", type="float", nullable=false)
     */
    protected $rateMinNormal;

    /**
     * @var float
     * @ORM\Column(name="interest_rate_default", type="float", nullable=false)
     */
    protected $interestRateDefault;

    /**
     * @var float
     * @ORM\Column(name="service_charge", type="float", nullable=false)
     */
    protected $serviceCharge;

    /**
     * @return ConfigPayment
     */
    public function getPaymentConfig()
    {
        return $this->paymentConfig;
    }

    /**
     * @param ConfigPayment $paymentConfig
     */
    public function setPaymentConfig($paymentConfig)
    {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @return array
     */
    public function getMonthsAllowed()
    {
        return $this->monthsAllowed;
    }

    /**
     * @param array $monthsAllowed
     */
    public function setMonthsAllowed($monthsAllowed)
    {
        $this->monthsAllowed = $monthsAllowed;
    }

    /**
     * @return bool
     */
    public function isBankTransferAllowed()
    {
        return $this->bankTransferAllowed;
    }

    /**
     * @param bool $bankTransferAllowed
     */
    public function setBankTransferAllowed(bool $bankTransferAllowed)
    {
        $this->bankTransferAllowed = $bankTransferAllowed;
    }

    /**
     * @return bool
     */
    public function isDebitAllowed()
    {
        return $this->debitAllowed;
    }

    /**
     * @param bool $debitAllowed
     */
    public function setDebitAllowed($debitAllowed)
    {
        $this->debitAllowed = $debitAllowed;
    }

    /**
     * @return string
     */
    public function getDefaultPaymentType()
    {
        return $this->defaultPaymentType;
    }

    /**
     * @param string $defaultPaymentType
     */
    public function setDefaultPaymentType($defaultPaymentType)
    {
        if (!PaymentFirstDay::getFirstDayForPayType($defaultPaymentType)) {
            throw new \InvalidArgumentException('payment type `' . $defaultPaymentType . '` does not exist');
        }
        $this->defaultPaymentType = $defaultPaymentType;
    }

    /**
     * @return float
     */
    public function getRateMinNormal()
    {
        return $this->rateMinNormal;
    }

    /**
     * @param float $rateMinNormal
     */
    public function setRateMinNormal($rateMinNormal)
    {
        $this->rateMinNormal = $rateMinNormal;
    }

    /**
     * @return float
     */
    public function getInterestRateDefault()
    {
        return $this->interestRateDefault;
    }

    /**
     * @param float $interestRateDefault
     */
    public function setInterestRateDefault($interestRateDefault)
    {
        $this->interestRateDefault = $interestRateDefault;
    }

    /**
     * @return float
     */
    public function getServiceCharge()
    {
        return $this->serviceCharge;
    }

    /**
     * @param float $serviceCharge
     */
    public function setServiceCharge($serviceCharge)
    {
        $this->serviceCharge = $serviceCharge;
    }

    public function getAllowedPaymentTypes()
    {
        $return = [];
        if ($this->isBankTransferAllowed()) {
            $return[] = PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;
        }

        if ($this->isDebitAllowed()) {
            $return[] = PaymentFirstDay::PAY_TYPE_DIRECT_DEBIT;
        }

        return $return;
    }
}
