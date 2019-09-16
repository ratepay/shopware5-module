<?php

namespace RpayRatePay\Subscriber\Backend;

use \Enlight\Event\SubscriberInterface;
use Monolog\Logger;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\WriterService;

class PluginConfigurationSubscriber implements SubscriberInterface
{
    protected $_countries = ['de', 'at', 'ch', 'nl', 'be'];

    /**
     * @var string
     */
    private $name;
    /**
     * @var ConfigService
     */
    protected $config;
    /**
     * @var WriterService
     */
    protected $configWriterService;
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        WriterService $configWriterService,
        ConfigService $configService,
        Logger $logger,
        $name
    )
    {
        $this->configWriterService = $configWriterService;
        $this->config = $configService;
        $this->logger = $logger;
        $this->name = $name;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Config::saveFormAction::before' => 'beforeSavePluginConfig',
        ];
    }

    /**
     * @param \Enlight_Hook_HookArgs $arguments
     * @throws \Exception
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
                // TODO hier ist etwas faul ! die nächsten schleifen haben als innere Variable "$element" als name. das geht nicht !
                if ($element['name'] === $this->config->getProfileIdKey($country, false)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileID'] = trim($element['value']);
                    }
                }
                if ($element['name'] === $this->config->getSecurityCodeKey($country, false)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCode'] = trim($element['value']);
                    }
                }
                if ($element['name'] === $this->config->getProfileIdKey($country, true)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileIDBackend'] = trim($element['value']);
                    }
                }
                if ($element['name'] === $this->config->getSecurityCodeKey($country, true)) {
                    foreach ($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCodeBackend'] = trim($element['value']);
                    }
                }
            }
        }

        $this->configWriterService->truncateConfigTables();

        $errors = [];

        foreach ($shopCredentials as $shopId => $credentials) {
            foreach ($this->_countries as $country) {
                if (null !== $credentials[$country]['profileID'] &&
                    null !== $credentials[$country]['securityCode']) {
                    if ($this->configWriterService->writeRatepayConfig($credentials[$country]['profileID'], $credentials[$country]['securityCode'], $shopId, $country)) {
                        $this->logger->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
                    } else {
                        $errors[] = strtoupper($country) . ' Frontend';
                    }

                    if ($country == 'de') {
                        if ($this->configWriterService->writeRatepayConfig($credentials[$country]['profileID'] . '_0RT', $credentials[$country]['securityCode'], $shopId, $country)) {
                            $this->logger->addNotice('Ruleset 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
                if (null !== $credentials[$country]['profileIDBackend'] &&
                    null !== $credentials[$country]['securityCodeBackend']) {
                    if ($this->configWriterService->writeRatepayConfig($credentials[$country]['profileIDBackend'], $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                        $this->logger->addNotice('Ruleset BACKEND for ' . strtoupper($country) . ' successfully updated.');
                    } else {
                        $errors[] = strtoupper($country) . ' Backend';
                    }
                    if ($country == 'de') {
                        if ($this->configWriterService->writeRatepayConfig($credentials[$country]['profileIDBackend'] . '_0RT', $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                            $this->logger->addNotice('Ruleset BACKEND 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
            }
        }

        if(count($errors) > 0) {
            throw new \Exception('Form could not be saved. The following settings have errors ' .
                implode(', ', $errors) . '.');
        }
    }
}
