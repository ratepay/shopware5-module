<?php

namespace RpayRatePay\Bootstrapping\Events;

use RatePAY\RequestBuilder;
use RpayRatePay\Component\Service\RatepayConfigWriter;
use RpayRatePay\Component\Service\Logger;

class PluginConfigurationSubscriber implements \Enlight\Event\SubscriberInterface
{
    protected $_countries = ['de', 'at', 'ch', 'nl', 'be'];

    /**
     * @var string
     */
    private $name;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PluginConfigurationSubscriber constructor.
     * @param $name string name of plugin
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * @param \Enlight_Hook_HookArgs $arguments
     *
     * @return null
     */
    public function beforeSavePluginConfig(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();

        if ($parameter['name'] !== $this->name || $parameter['controller'] !== 'config') {
            return;
        }

        $shopCredentials = [];

        foreach ($parameter['elements'] as $element) {
            foreach ($this->_countries as $country) {
                if ($element['name'] === 'RatePayProfileID' . strtoupper($country)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileID'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePaySecurityCode' . strtoupper($country)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCode'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePayProfileID' . strtoupper($country) . 'Backend') {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileIDBackend'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePaySecurityCode' . strtoupper($country) . 'Backend') {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCodeBackend'] = trim($element['value']);
                    }
                }
            }
        }

        $rpayConfigWriter = new RatepayConfigWriter(Shopware()->Db());

        $rpayConfigWriter->truncateConfigTables();

        foreach ($shopCredentials as $shopId => $credentials) {
            foreach ($this->_countries as $country) {
                if (null !== $credentials[$country]['profileID'] &&
                    null !== $credentials[$country]['securityCode']) {
                    if ($rpayConfigWriter->writeRatepayConfig($credentials[$country]['profileID'], $credentials[$country]['securityCode'], $shopId, $country)) {
                        Logger::singleton()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
                    }
                    if ($country == 'de') {
                        if ($rpayConfigWriter->writeRatepayConfig($credentials[$country]['profileID'] . '_0RT', $credentials[$country]['securityCode'], $shopId, $country)) {
                            Logger::singleton()->addNotice('Ruleset 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
                if (null !== $credentials[$country]['profileIDBackend'] &&
                    null !== $credentials[$country]['securityCodeBackend']) {
                    if ($rpayConfigWriter->writeRatepayConfig($credentials[$country]['profileIDBackend'], $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                        Logger::singleton()->addNotice('Ruleset BACKEND for ' . strtoupper($country) . ' successfully updated.');
                    }
                    if ($country == 'de') {
                        if ($rpayConfigWriter->writeRatepayConfig($credentials[$country]['profileIDBackend'] . '_0RT', $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                            Logger::singleton()->addNotice('Ruleset BACKEND 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
            }
        }
    }
}
