<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:58
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PluginConfigurationSubscriber implements \Enlight\Event\SubscriberInterface
{
    protected $_countries = array('de', 'at', 'ch', 'nl', 'be');

    /**
     * @var Shopware_Components_Plugin_Bootstrap
     */
    private $bootstrap;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_LoggingControllerSubscriber constructor.
     * @param Shopware_Components_Plugin_Bootstrap $bootstrap
     */
    public function __construct($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Config::saveFormAction::before' => 'beforeSavePluginConfig',
        ];
    }

    /**
     * Checks if credentials are set and gets the configuration via profile_request
     *
     * @param Enlight_Hook_HookArgs $arguments
     *
     * @return null
     */
    public function beforeSavePluginConfig(Enlight_Hook_HookArgs $arguments)
    {

        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();

        if ($parameter['name'] !== $this->bootstrap->getName() || $parameter['controller'] !== 'config') {
            return;
        }

//        $credentials = array();
//
//        //Remove old configs
//        $this->_truncateConfigTable();
//        $countries = $this->_countries;
//
//        foreach ($parameter['elements'] as $element) {
//            foreach ($countries AS $country) {
//                if ($element['name'] === 'RatePayProfileID' . strtoupper($country)) {
//                    foreach($element['values'] as $element) {
//                        $credentials[$element['shopId']][$country]['profileID'] = $element['value'];
//                    }
//                }
//                if ($element['name'] === 'RatePaySecurityCode'  . strtoupper($country)) {
//                    foreach($element['values'] as $element) {
//                        $credentials[$element['shopId']][$country]['securityCode'] = $element['value'];
//                    }
//                }
//            }
//        }
//
//        foreach($credentials as $shopId => $credentials) {
//            foreach ($countries AS $country) {
//                if (null !== $credentials[$country]['profileID']
//                    && null !== $credentials[$country]['securityCode']
//                )
//                {
//                    if ($this->getRatepayConfig($credentials[$country]['profileID'], $credentials[$country]['securityCode'], $shopId, $country)) {
//                        Shopware()->PluginLogger()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
//                    }
//                    if ($country == 'de') {
//                        if ($this->getRatepayConfig($credentials[$country]['profileID'] . '_0RT', $credentials[$country]['securityCode'], $shopId, $country)) {
//                            Shopware()->PluginLogger()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
//                        }
//                    }
//                }
//            }
//        }
    }
}