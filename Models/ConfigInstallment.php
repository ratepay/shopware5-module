<?php

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity(repositoryClass="ConfigInstallmentRepository")
 * @ORM\Table(name="rpay_ratepay_config_installment")
 */
class ConfigInstallment extends ModelEntity
{
    /**
     * @var ConfigPayment
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="RpayRatePay\Models\ConfigPayment")
     * @ORM\JoinColumn(name="rpay_id", referencedColumnName="rpay_id")
     */
    protected $paymentConfig;

    /**
     * @var string
     * @ORM\Column(name="month_allowed", type="string", length=255, nullable=false)
     */
    protected $monthAllowed;

    /**
     * @var string
     * @ORM\Column(name="payment_firstday", type="string", length=10, nullable=false)
     */
    protected $paymentFirstDay;

    /**
     * @var string
     * @ORM\Column(name="interestrate_default", type="float", nullable=false)
     */
    protected $interestRateDateDefault;

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
    public function getMonthAllowed()
    {
        return $this->monthAllowed;
    }

    /**
     * @param string $monthAllowed
     */
    public function setMonthAllowed($monthAllowed)
    {
        $this->monthAllowed = $monthAllowed;
    }

    /**
     * @return string
     */
    public function getPaymentFirstDay()
    {
        return $this->paymentFirstDay;
    }

    /**
     * @param string $paymentFirstDay
     */
    public function setPaymentFirstDay($paymentFirstDay)
    {
        $this->paymentFirstDay = $paymentFirstDay;
    }

    /**
     * @return string
     */
    public function getInterestRateDateDefault()
    {
        return $this->interestRateDateDefault;
    }

    /**
     * @param string $interestRateDateDefault
     */
    public function setInterestRateDateDefault($interestRateDateDefault)
    {
        $this->interestRateDateDefault = $interestRateDateDefault;
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
