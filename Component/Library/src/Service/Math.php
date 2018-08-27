<?php

namespace RatePAY\Service;


/**
 * Class Math static functions that do reusable calculations.
 * @package RatePAY\Service
 */
class Math
{

    /**
     * @param float $netPrice
     * @param float|int $taxPercentage
     * @param bool $round
     * @return float
     */
    public static function netToGross($netPrice, $taxPercentage, $round = false)
    {
        $withTax = $netPrice + $netPrice * $taxPercentage / 100;

        if (!$round) {
            return $withTax;
        }

        $rounded = round($withTax, 2);

        return $rounded;
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