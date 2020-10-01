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
use RpayRatePay\Models\Log;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * Logging
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
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
        preg_match("/<operation.*>(.*)<\/operation>/", $requestXml, $operationMatches);
        $operation = $operationMatches[1];

        preg_match('/<operation subtype=\"(.*)">(.*)<\/operation>/', $requestXml, $operationSubtypeMatches);
        $operationSubtype = isset($operationSubtypeMatches[1]) ? $operationSubtypeMatches[1] : 'N/A';

        preg_match("/<transaction-id>(.*)<\/transaction-id>/", $requestXml, $transactionMatches);
        $transactionId = isset($transactionMatches[1]) ? $transactionMatches[1] : 'N/A';

        preg_match("/<transaction-id>(.*)<\/transaction-id>/", $responseXml, $transactionMatchesResponse);
        $transactionId = $transactionId == 'N/A' && isset($transactionMatchesResponse[1]) ? $transactionMatchesResponse[1] : $transactionId;

        $requestXml = preg_replace("/<owner>(.*)<\/owner>/", '<owner>xxxxxxxx</owner>', $requestXml);
        $requestXml = preg_replace("/<bank-account-number>(.*)<\/bank-account-number>/", '<bank-account-number>xxxxxxxx</bank-account-number>', $requestXml);
        $requestXml = preg_replace("/<bank-code>(.*)<\/bank-code>/", '<bank-code>xxxxxxxx</bank-code>', $requestXml);

        try {
            $log = new Log();
            $log->setVersion($this->config->getPluginVersion());
            $log->setOperation($operation);
            $log->setSubOperation($operationSubtype);
            $log->setTransationId($transactionId);
            $log->setRequest($requestXml);
            $log->setResponse($responseXml);

            $this->modelManager->persist($log);
            $this->modelManager->flush($log);
        } catch (Exception $exception) {
            $this->logger->error('RatePAY was unable to log order history: ' . $exception->getMessage());
        }
    }
}
