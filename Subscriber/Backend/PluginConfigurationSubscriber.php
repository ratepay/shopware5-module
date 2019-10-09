<?php

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
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
     * @param Enlight_Hook_HookArgs $arguments
     * @throws Exception
     */
    public function beforeSavePluginConfig(Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();

        if ($parameter['name'] !== $this->name || $parameter['controller'] !== 'config') {
            return;
        }

        $shopCredentials = [];

        foreach ($parameter['elements'] as $element) {

            $matches = [];
            if(preg_match_all('/ratepay\/profile\/([a-z]{2})\/(frontend|backend)\/(id|security_code)\/?(installment0)?/', $element['name'], $matches)) {
                foreach($element['values'] as $valueArray) {
                    $shopId = $valueArray['shopId'];
                    $value = trim($valueArray['value']);

                    $country = $matches[1][0];
                    $scope = $matches[2][0]; // frontend | backend
                    $fieldName = $matches[3][0]; // id | security_code
                    $profileType = $matches[4][0] === 'installment0' ? 'installment0' : 'general';
                    $shopCredentials[$shopId][$country][$scope][$profileType][$fieldName] = $value;
                }
            }
        }

        $this->configWriterService->truncateConfigTables();

        $errors = [];

        foreach ($shopCredentials as $shopId => $countries) { // de | at | nl | ch | be
            foreach($countries as $countryCode => $scopes) { // backend | frontend
                foreach($scopes as $scope => $profileTypes) {  // general | installment0
                    foreach ($profileTypes as $type => $credentials) {
                        if (null !== $credentials['id'] && null !== $credentials['security_code']) {

                            $saveResponse = $this->configWriterService->writeRatepayConfig(
                                $credentials['id'],
                                $credentials['security_code'],
                                $shopId,
                                $countryCode,
                                $type == 'installment0',
                                $scope == 'backend'
                            );

                            if ($saveResponse) {
                                $this->logger->addNotice('Ruleset for ' . strtoupper($countryCode) . ' successfully updated.');
                            } else {
                                $errors[] = strtoupper($countryCode) . ' Frontend';
                            }
                        }
                    }
                }
            }
        }

        if (count($errors) > 0) {
            throw new Exception('Form could not be saved. The following settings have errors ' .
                implode(', ', $errors) . '.');
        }
    }
}
