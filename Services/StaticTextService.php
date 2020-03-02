<?php


namespace RpayRatePay\Services;


class StaticTextService
{
    /**
     * @var StaticTextService
     */
    private static $instance;

    /**
     * @var string
     */
    private $pluginDir;

    private function __construct()
    {
        $this->pluginDir = __DIR__ . '/../';
    }

    public static function getInstance()
    {
        return self::$instance = (self::$instance ?: new self());
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
