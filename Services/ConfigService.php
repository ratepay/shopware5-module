<?php


namespace RpayRatePay\Services;


class ConfigService
{

    //TODO remove if plugin is moved to SW5.2 plugin engine
    private static $instance = null;
    public static function getInstance(){
        return self::$instance = (self::$instance ? : new self(Shopware()->Config()));
    }

    private $_config;

    public function __construct(\Shopware_Components_Config $config)
    {
        $this->_config = $config;
    }

    public function getDfpSnippetId() {
        $defaultValue = 'ratepay';
        $value = $this->_config->get('ratepay/dfp/snippet_id', $defaultValue);
        return strlen($value) ? $value : $defaultValue;
    }

}
