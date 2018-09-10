<?php

namespace RatePAY\Service;
use RpayRatePay\Component\Service\Logger;


/**
 * Class Math static functions that do reusable calculations.
 * @package RatePAY\Service
 */
class Math
{

    /**
     * @param float $netPrice
     * @param float|int $taxPercentage
     * @param bool $roundToTwoDecimals
     * @return float
     */
    public static function netToGross($netPrice, $taxPercentage, $roundToTwoDecimals = false)
    {
        $withTax = $netPrice + ($netPrice * $taxPercentage / 100);
        $precision = $roundToTwoDecimals ? 2 : 3;

        return round($withTax, $precision);
    }

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