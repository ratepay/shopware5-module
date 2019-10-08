<?php


namespace RpayRatePay\DTO;


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


    public function __construct($productNumber, $quantity)
    {
        $this->productNumber = $productNumber;
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
}
