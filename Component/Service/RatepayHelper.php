<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 23.10.18
 * Time: 13:42
 */

namespace RpayRatePay\Component\Service;


class RatepayHelper
{
    /**
     * @param $paymentMethod
     * @return bool
     */
    public static function isRatePayPayment($paymentMethod)
    {
        return in_array($paymentMethod, self::getPaymentMethods());
    }

    public static function getPaymentMethods()
    {
        return [
            'rpayratepayinvoice',
            'rpayratepayrate',
            'rpayratepaydebit',
            'rpayratepayrate0',
            'rpayratepayprepayment',
        ];
    }
}