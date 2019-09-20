<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RatePAY\Model\Response\AbstractResponse;
use RatePAY\ModelBuilder;
use RatePAY\RequestBuilder;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Logger\RequestLogger;

abstract class AbstractRequest
{

    const CALL_PAYMENT_REQUEST = "paymentRequest";
    const CALL_PAYMENT_CONFIRM = "paymentConfirm";
    const CALL_DELIVER = "confirmationDeliver";
    const CALL_CHANGE = "paymentChange";

    /**
     * @return string
     */
    abstract protected function getCallName();
    /**
     * @return array
     */
    abstract protected function getRequestContent();

    /**
     * @param $isBackend
     * @return ProfileConfig
     */
    abstract protected function getProfileConfig();
    abstract protected function processSuccess();

    protected function isSkipRequest() {
        return false;
    }


    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;

    /**
     * @var RequestLogger
     */
    protected $requestLogger;

    protected $_subType = null;

    /** @var bool  */
    protected $isRequestSkipped = false;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger
    )
    {
        $this->db = $db;
        $this->configService = $configService;
        $this->profileConfigService = $profileConfigService;
        $this->requestLogger = $requestLogger;
    }

    /**
     * @return AbstractResponse
     */
    public final function doRequest() {
        /** @var AbstractResponse $response */
        $response = $this->call($this->getRequestContent(), false);
        if($response->isSuccessful()) {
            $this->processSuccess();
        }
        return $response;
    }

    protected final function call(array $content = null, $isRetry = false) {
        if($this->isSkipRequest()) {
            $this->isRequestSkipped = true;
            return true;
        }
        $profileConfig = $this->getProfileConfig();

        $mbHead = new ModelBuilder('head');
        $mbHead->setArray($this->getRequestHead($profileConfig));

        $mbContent = null;
        if($content) {
            $mbContent = new ModelBuilder('Content');
            $mbContent->setArray($content);
        }

        $rb = new RequestBuilder($profileConfig->isSandbox());
        $rb = $rb->__call('call'.ucfirst($this->getCallName()), $mbContent ? [$mbHead, $mbContent] : [$mbHead]);
        if($this->_subType) {
            $rb = $rb->subtype($this->_subType);
        }

        //yes this is "correct" - all functions with "get" or "is" as prefix will piped to this (abstract) model
        /** @var AbstractResponse $responseModel */
        $responseModel = $rb;

        $this->requestLogger->logRequest($rb->getRequestRaw(), $rb->getResponseRaw());

        if ($responseModel->isSuccessful()) {
            return $responseModel;
        } elseif ($isRetry === false && intval($responseModel->getReasonCode()) == 2300) {
            return $this->call($content, true);
        } else {
            return $responseModel;
        }
    }

    protected function getRequestHead(ProfileConfig $profileConfig) {
        $head = [
            'SystemId' => $this->getSystemId(),
            'Credential' => [
                'ProfileId' => $profileConfig->getProfileId(),
                'Securitycode' => $profileConfig->getSecurityCode()
            ],
            'Meta' => [
                'Systems' => [
                    'System' => [
                        'Name' => 'Shopware',
                        'Version' => Shopware()->Config()->get('version') . '/' . $this->configService->getPluginVersion()
                    ]
                ]
            ]
        ];
        return $head;
    }

    private function getSystemId()
    {
        return $this->db->fetchOne('SELECT `host` FROM `s_core_shops` WHERE `default`=1') ? : $_SERVER['SERVER_ADDR'];
    }

}
