<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RatePAY\Model\Response\AbstractResponse;
use RatePAY\ModelBuilder;
use RatePAY\RequestBuilder;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Logger\RequestLogger;

abstract class AbstractRequest
{

    const CALL_PAYMENT_REQUEST = "paymentRequest";
    const CALL_PAYMENT_CONFIRM = "paymentConfirm";
    const CALL_DELIVER = "confirmationDeliver";
    const CALL_CHANGE = "paymentChange";
    const CALL_PROFILE_REQUEST = "profileRequest";
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var RequestLogger
     */
    protected $requestLogger;
    protected $_subType = null;
    /** @var bool */
    protected $isRequestSkipped = false;
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        RequestLogger $requestLogger
    )
    {
        $this->db = $db;
        $this->configService = $configService;
        $this->requestLogger = $requestLogger;
    }

    final public function doRequest()
    {
        /*S* @var AbstractResponse $response */
        $response = $this->call(null, false);
        if ($response === true || $response->isSuccessful()) {
            $this->processSuccess();
        } else {
            $this->processFailed($response);
        }
        return $response;
    }

    private function call(array $content = null, $isRetry = false)
    {
        if ($this->isSkipRequest()) {
            $this->isRequestSkipped = true;
            return true;
        }
        $profileConfig = $this->getProfileConfig();
        if ($profileConfig === null) {
            throw new NoProfileFoundException();
        }
        $content = $content ?: $this->getRequestContent();

        $mbHead = new ModelBuilder('head');
        $mbHead->setArray($this->getRequestHead($profileConfig));

        $mbContent = null;
        if ($content) {
            $mbContent = new ModelBuilder('Content');
            $mbContent->setArray($content);
        }

        $rb = new RequestBuilder($profileConfig->isSandbox());
        $rb = $rb->__call('call' . ucfirst($this->getCallName()), $mbContent ? [$mbHead, $mbContent] : [$mbHead]);
        if ($this->_subType) {
            $rb = $rb->subtype($this->_subType);
        }

        $this->requestLogger->logRequest($rb->getRequestRaw(), $rb->getResponseRaw());

        if ($rb->getResponse()->isSuccessful()) {
            return $rb;
        }

        if ($isRetry === false && ((int) $rb->getResponse()->getReasonCode()) === 2300) {
            return $this->call($content, true);
        }
        return $rb;
    }

    protected function isSkipRequest()
    {
        return false;
    }

    /**
     * @return array
     */
    abstract protected function getRequestContent();

    /**
     * @param $isBackend
     * @return ProfileConfig
     */
    abstract protected function getProfileConfig();

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
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
        return $this->db->fetchOne('SELECT `host` FROM `s_core_shops` WHERE `default`=1') ?: $_SERVER['SERVER_ADDR'];
    }

    /**
     * @return string
     */
    abstract protected function getCallName();

    abstract protected function processSuccess();

    /**
     * The response object is included in the RequestBuilder.
     * @param RequestBuilder $requestBuilder
     */
    protected function processFailed(RequestBuilder $requestBuilder)
    {
        // do nothing
    }

}
