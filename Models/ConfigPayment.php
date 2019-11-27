<?php

namespace RpayRatePay\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_config_payment")
 */
class ConfigPayment extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="rpay_id", type="integer", length=2, nullable=false)
     */
    protected $rpayId;

    /**
     * @var boolean
     * @ORM\Column(name="b2b", type="boolean")
     */
    protected $b2b;
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
    public function getRpayId()
    {
        return $this->rpayId;
    }

    /**
     * @param int $rpayId
     */
    public function setRpayId($rpayId)
    {
        $this->rpayId = $rpayId;
    }

    /**
     * @return boolean
     */
    public function isB2b()
    {
        return $this->b2b;
    }

    /**
     * @param boolean $b2b
     */
    public function setB2b($b2b)
    {
        $this->b2b = $b2b;
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
     * @return int
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param int $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
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
