<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
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
     * @var string
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
     * @ORM\Column(name="rate_min_normal", type="float", nullable=false)
     */
    protected $rateMinNormal;

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
     * @return string
     */
    public function getMonthsAllowed()
    {
        return $this->monthsAllowed;
    }

    /**
     * @param string $monthsAllowed
     */
    public function setMonthsAllowed($monthsAllowed)
    {
        $this->monthsAllowed = $monthsAllowed;
    }

    /**
     * @return bool
     */
    public function isBankTransferAllowed(): bool
    {
        return $this->bankTransferAllowed;
    }

    /**
     * @param bool $bankTransferAllowed
     */
    public function setBankTransferAllowed(bool $bankTransferAllowed): void
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
     * @deprecated
     */
    public function getPaymentFirstDay()
    {
        if ($this->isDebitAllowed() && $this->isBankTransferAllowed()) {
            return '2,28';
        }
        if ($this->isDebitAllowed()) {
            return 2;
        }
        if ($this->isBankTransferAllowed()) {
            return 28;
        }
        throw new \RuntimeException('unknown paymentPaymentFirstDay');
    }

    /**
     * @return string
     */
    public function getRateMinNormal()
    {
        return $this->rateMinNormal;
    }

    /**
     * @param string $rateMinNormal
     */
    public function setRateMinNormal($rateMinNormal)
    {
        $this->rateMinNormal = $rateMinNormal;
    }
}
