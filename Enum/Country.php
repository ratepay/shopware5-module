<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Enum;


final class Country
{
    const AVAILABLE_COUNTRIES = [
        'DE',
        'AT',
        'CH',
        'NL',
        'BE'
    ];

    public static function getLowerCountries()
    {
        return array_map(function ($value) {
            return strtolower($value);
        }, self::AVAILABLE_COUNTRIES);
    }

    public static function getCountries()
    {
        return self::AVAILABLE_COUNTRIES;
    }

}
