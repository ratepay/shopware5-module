<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


use RpayRatePay\Enum\PaymentMethods;
use Shopware\Models\Order\Order;

class HelperService
{


    public function __construct()
    {
    }

    //TODO maybe param support for int?
    public function isRatePayPayment(Order $order)
    {
        return PaymentMethods::exists($order->getPayment()->getName());
    }
}
