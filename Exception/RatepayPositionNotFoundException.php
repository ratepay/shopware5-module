<?php


namespace RpayRatePay\Exception;


use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class RatepayPositionNotFoundException extends RatepayException
{

    /**
     * @param Order $order
     * @param Detail $detail
     * @param string $expectedClass
     */
    public function __construct(Order $order, Detail $detail, $expectedClass)
    {
        parent::__construct('Ratepay position for order detail has not been found.', null, null, [
            'order_id' => $order->getId(),
            'order_number' => $order->getNumber(),
            'order_detail_id' => $detail->getId(),
            'expected_model' => $expectedClass
        ]);
    }

}