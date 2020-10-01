<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;

class Invoice extends AbstractPaymentMethod
{

    protected function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        //do nothing
    }
}
