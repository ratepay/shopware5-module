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
use Shopware\Models\Payment\Payment;

/**
 * @ORM\Entity(repositoryClass="RpayRatePay\Models\PaymentConfigRepository")
 * @ORM\Table(name="ratepay_profile_config_method")
 */
class ConfigPayment extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer", length=2, nullable=false)
     */
    protected $id;

    /**
     * @var ProfileConfig
     * @ORM\ManyToOne(targetEntity="RpayRatePay\Models\ProfileConfig")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     */
    protected $profileConfig;

    /**
     * @var Payment
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Payment\Payment", fetch="LAZY")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     */
    protected $paymentMethod;

    /**
     * @var boolean
     * @ORM\Column(name="allow_b2b", type="boolean")
     */
    protected $allowB2b;

    /**
     * @var int
     * @ORM\Column(name="limit_min", type="integer", nullable=false)
     */
    protected $limitMin;

    /**
     * @var int
     * @ORM\Column(name="limit_max", type="integer", nullable=false)
     */
    protected $limitMax;

    /**
     * @var int
     * @ORM\Column(name="limit_max_b2b", type="integer", nullable=true)
     */
    protected $limitMaxB2b;

    /**
     * @var boolean
     * @ORM\Column(name="allow_different_addresses", type="boolean")
     */
    protected $allowDifferentAddresses;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $rpayId
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ProfileConfig
     */
    public function getProfileConfig()
    {
        return $this->profileConfig;
    }

    /**
     * @param ProfileConfig $profileConfig
     */
    public function setProfileConfig(ProfileConfig $profileConfig)
    {
        $this->profileConfig = $profileConfig;
    }

    /**
     * @return Payment
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param Payment $paymentMethod
     */
    public function setPaymentMethod(Payment $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return boolean
     */
    public function isAllowB2B()
    {
        return $this->allowB2b;
    }

    /**
     * @param boolean $allowB2b
     */
    public function setAllowB2B($allowB2b)
    {
        $this->allowB2b = $allowB2b;
    }

    /**
     * @return int
     */
    public function getLimitMin()
    {
        return $this->limitMin;
    }

    /**
     * @param int $limitMin
     */
    public function setLimitMin($limitMin)
    {
        $this->limitMin = $limitMin;
    }

    /**
     * @return int
     */
    public function getLimitMax()
    {
        return $this->limitMax;
    }

    /**
     * @param int $limitMax
     */
    public function setLimitMax($limitMax)
    {
        $this->limitMax = $limitMax;
    }

    /**
     * @return int
     */
    public function getLimitMaxB2b()
    {
        return $this->limitMaxB2b;
    }

    /**
     * @param int $limitMaxB2b
     */
    public function setLimitMaxB2b($limitMaxB2b)
    {
        $this->limitMaxB2b = $limitMaxB2b;
    }

    /**
     * @return bool
     */
    public function isAllowDifferentAddresses()
    {
        return $this->allowDifferentAddresses;
    }

    /**
     * @param bool $allowDifferentAddresses
     */
    public function setAllowDifferentAddresses($allowDifferentAddresses)
    {
        $this->allowDifferentAddresses = $allowDifferentAddresses;
    }
}
