<?php


namespace RpayRatePay\Services;


use RpayRatePay\Enum\PaymentMethods;
use Shopware\Models\Order\Order;

class HelperService
{


    public function __construct()
    {
    }

    //TODO maybe param support for int?
    public function isRatePayPayment(Order $order) {
        return PaymentMethods::exists($order->getPayment()->getName());
    }
}
