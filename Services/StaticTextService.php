<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


class StaticTextService
{

    /**
     * @var string
     */
    private $pluginDir;

    public function __construct($pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    public function getText($key)
    {
        $legalTexts = parse_ini_file($this->pluginDir . '/Resources/data/static_text.ini', true);
        $locale = Shopware()->Shop()->getLocale()->getLocale();
        if (!isset($legalTexts[$locale][$key])) {
            $locale = 'en_GB';
        }
        return isset($legalTexts[$locale][$key]) ? $legalTexts[$locale][$key] : null;

    }

}
