<?php declare(strict_types=1);
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Helper;


use RatePAY\Service\LanguageService;
use Shopware\Models\Shop\Shop;

class LanguageHelper
{

    /**
     * returns Ratepay translations from the SDK relates to the locale of the given shop
     * @param Shop $shop
     * @return array
     */
    public static function getRatepayTranslations(Shop $shop)
    {
        return (new LanguageService(substr($shop->getLocale()->getLocale(), 3, 5)))->getArray();
    }

}
