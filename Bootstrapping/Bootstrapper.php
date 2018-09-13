<?php

namespace RpayRatePay\Bootstrapping;

abstract class Bootstrapper
{
    /**
     * @var Shopware_Components_Plugin_Bootstrap
     */
    protected $bootstrap;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Payments constructor.
     * @param Shopware_Components_Plugin_Bootstrap $bootstrap
     */
    public function __construct($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @return mixed
     */
    abstract public function install();

    /**
     * @return mixed
     */
    abstract public function update();

    /**
     * @return mixed
     */
    abstract public function uninstall();

    /**
     * @param $configFile
     * @return mixed
     * @throws Exception
     */
    public function loadConfig($configFile)
    {
        if (!empty($configFile) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $configFile)) {
            return json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $configFile), true);
        }

        throw new Exception("Unable to load configuration file '$configFile'");
    }

    public function getName()
    {
        return end(explode('_', get_class($this)));
    }
}
