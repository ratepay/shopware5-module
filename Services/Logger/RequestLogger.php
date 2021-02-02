<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Logger;

use Exception;
use Monolog\Logger;
use RpayRatePay\Helper\XmlHelper;
use RpayRatePay\Models\Log;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;

class RequestLogger
{

    /**
     * @var ConfigService
     */
    protected $config;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ConfigService $config,
        ModelManager $modelManager,
        Logger $logger
    )
    {
        $this->config = $config;
        $this->modelManager = $modelManager;
        $this->logger = $logger;
    }

    /**
     * Logs the Request and Response
     *
     * @param string $requestXml
     * @param string $responseXml
     */
    public function logRequest($requestXml, $responseXml)
    {
        $operation = XmlHelper::findValue($requestXml, 'operation');
        $operationSubtype = XmlHelper::findAttributeValue($requestXml, 'operation', 'subtype');
        $transactionId = XmlHelper::findValue($requestXml, 'transaction-id');
        $transactionId = XmlHelper::findValue($responseXml, 'transaction-id', $transactionId);

        $requestXml = preg_replace("/<owner>(.*)<\/owner>/", '<owner>xxxxxxxx</owner>', $requestXml);
        $requestXml = preg_replace("/<bank-account-number>(.*)<\/bank-account-number>/", '<bank-account-number>xxxxxxxx</bank-account-number>', $requestXml);
        $requestXml = preg_replace("/<bank-code>(.*)<\/bank-code>/", '<bank-code>xxxxxxxx</bank-code>', $requestXml);

        try {
            $log = new Log();
            $log->setVersion($this->config->getPluginVersion());
            $log->setOperation($operation);
            $log->setSubOperation($operationSubtype);
            $log->setTransactionId($transactionId);
            $log->setRequest($requestXml);
            $log->setResponse($responseXml);

            $this->modelManager->persist($log);
            $this->modelManager->flush($log);
        } catch (Exception $exception) {
            $this->logger->error('Ratepay was unable to log order history: ' . $exception->getMessage());
        }
    }
}
