<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models\Position;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Order\Detail;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_order_positions")
 */
class Product extends AbstractPosition
{

    /**
     * @var Detail
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="Shopware\Models\Order\Detail")
     * @ORM\JoinColumn(name="s_order_details_id", referencedColumnName="id")
     */
    protected $orderDetail;

    /**
     * @return Detail
     */
    public function getOrderDetail()
    {
        return $this->orderDetail;
    }

    /**
     * @param Detail $orderDetail
     */
    public function setOrderDetail(Detail $orderDetail)
    {
        $this->orderDetail = $orderDetail;
    }

    public function getOrderedQuantity()
    {
        return $this->orderDetail->getQuantity();
    }
}
