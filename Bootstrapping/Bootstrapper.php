<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 14:02
 */

abstract class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    protected $_configSrcPath = __DIR__ . DIRECTORY_SEPARATOR;

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
    public abstract function install();

    /**
     * @return mixed
     */
    public abstract function update();

    /**
     * @return mixed
     */
    public abstract function uninstall();

    /**
     * @param $configFile
     * @return mixed
     * @throws Exception
     */
    public function loadConfig($configFile)
    {
        if (!empty($configFile) && file_exists($this->_configSrcPath . $configFile)) {
            return json_decode(file_get_contents($this->_configSrcPath . $configFile), true);
        }

        throw new Exception("Unable to load configuration file '$configFile'");
    }

    public function getName()
    {
        return end(explode('_', get_class($this)));
    }
}