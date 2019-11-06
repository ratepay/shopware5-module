<?php


namespace RpayRatePay\DTO;


use Shopware\Models\Order\Detail as OrderDetail;

class BasketPosition
{

    const SHIPPING_NUMBER = 'shipping';

    /**
     * @var string
     */
    private $productNumber;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var OrderDetail
     */
    private $orderDetail;


    /**
     * BasketPosition constructor.
     * @param OrderDetail|$productNumber
     * @param $quantity
     */
    public function __construct($productNumber, $quantity)
    {
        if($productNumber instanceof OrderDetail) {
            $this->orderDetail = $productNumber;
        } else {
            $this->productNumber = $productNumber;
        }
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getProductNumber()
    {
        return $this->productNumber;
    }

    /**
     * @param string $productNumber
     */
    public function setProductNumber($productNumber)
    {
        $this->productNumber = $productNumber;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return OrderDetail
     */
    public function getOrderDetail()
    {
        return $this->orderDetail;
    }

    /**
     * @param OrderDetail $orderDetail
     */
    public function setOrderDetail($orderDetail)
    {
        $this->orderDetail = $orderDetail;
    }
}
