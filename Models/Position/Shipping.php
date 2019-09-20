<?php

namespace RpayRatePay\Models\Position;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_order_shipping")
 */
class Shipping extends AbstractPosition
{
    /**
     * TODO replace by order model
     * @var integer
     * @ORM\Id()
     * @ORM\Column(name="s_order_id", type="integer", length=11, nullable=false)
     */
    protected $sOrderId;

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

    public function getOrderedQuantity()
    {
        return 1;
    }
}
