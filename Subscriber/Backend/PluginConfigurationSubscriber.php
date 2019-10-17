<?php

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Monolog\Logger;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
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

    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;

    public function __construct(
        WriterService $configWriterService,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        Logger $logger,
        $name
    )
    {
        $this->configWriterService = $configWriterService;
        $this->config = $configService;
        $this->profileConfigService = $profileConfigService;
        $this->logger = $logger;
        $this->name = $name;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Config::saveFormAction::after' => 'beforeSavePluginConfig',
        ];
    }

    /**
     * @param Enlight_Hook_HookArgs $arguments
     * @throws Exception
     */
    public function beforeSavePluginConfig(Enlight_Hook_HookArgs $arguments)
    {
        /** @var \Shopware_Controllers_Backend_Config $controller */
        $controller = $arguments->getSubject();
        $request = $controller->Request();
        $parameter = $request->getParams();

        if ($parameter['name'] !== $this->name || $parameter['controller'] !== 'config') {
            return;
        }

        $shopCredentials = [];

        foreach ($parameter['elements'] as $element) {

            $matches = [];
            if (preg_match_all(ProfileConfigService::REGEX_CONFIG, $element['name'], $matches)) {
                foreach ($element['values'] as $valueArray) {
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

        $this->profileConfigService->refreshProfileConfigs($shopCredentials);

        if (count($errors) > 0) {
            throw new Exception('Form could not be saved. The following settings have errors ' .
                implode(', ', $errors) . '.');
        }
    }
}
