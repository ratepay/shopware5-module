<?php


namespace RpayRatePay\Helper;


class TaxHelper
{
    /**
     * @param float|int $netPrice
     * @param float|int $grossPrice
     * @return float|int
     */
    public static function taxFromPrices($netPrice, $grossPrice)
    {
        $tax = ($grossPrice - $netPrice) / $netPrice;
        return $tax * 100;
    }
}
