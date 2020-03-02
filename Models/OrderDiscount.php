<?php

namespace RpayRatePay\Models;


use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_order_discount")
 */
class OrderDiscount extends ModelEntity implements IPosition
{
    /**
     * @var integer
     * @ORM\Id()
     * @ORM\Column(name="s_order_id", type="integer", length=11, nullable=false)
     */
    protected $sOrderId;
    /**
     * @var integer
     * @ORM\Id()
     * @ORM\Column(name="s_order_detail_id", type="integer", length=11, nullable=false)
     */
    protected $sOrderDetailId;
    /**
     * @var integer
     * @ORM\Column(name="delivered", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $delivered;
    /**
     * @var integer
     * @ORM\Column(name="cancelled", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $cancelled;
    /**
     * @var integer
     * @ORM\Column(name="returned", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $returned;
    /**
     * @var integer
     * @ORM\Column(name="tax_rate", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $taxRate;

    /**
     * @return int
     */
    public function getSOrderId()
    {
        return $this->sOrderId;
    }

    /**
     * @param int $sOrderId
     */
    public function setSOrderId($sOrderId)
    {
        $this->sOrderId = $sOrderId;
    }

    /**
     * @return int
     */
    public function getSOrderDetailId()
    {
        return $this->sOrderDetailId;
    }

    /**
     * @param int $sOrderDetailId
     */
    public function setSOrderDetailId($sOrderDetailId)
    {
        $this->sOrderDetailId = $sOrderDetailId;
    }

    /**
     * @return int
     */
    public function getDelivered()
    {
        return $this->delivered;
    }

    /**
     * @param int $delivered
     */
    public function setDelivered($delivered)
    {
        $this->delivered = $delivered;
    }

    /**
     * @return int
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }

    /**
     * @param int $cancelled
     */
    public function setCancelled($cancelled)
    {
        $this->cancelled = $cancelled;
    }

    /**
     * @return int
     */
    public function getReturned()
    {
        return $this->returned;
    }

    /**
     * @param int $returned
     */
    public function setReturned($returned)
    {
        $this->returned = $returned;
    }

    /**
     * @return int
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param int $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }
}
