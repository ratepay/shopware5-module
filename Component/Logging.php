<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Service\Logger;

class Shopware_Plugins_Frontend_RpayRatePay_Component_Logging
{
    /**
     * Logs the Request and Response
     *
     * @param string $requestXml
     * @param string $responseXml
     */
    public function logRequest($requestXml, $responseXml)
    {
        $version = Shopware()->Plugins()->Frontend()->RpayRatePay()->getVersion();

        preg_match("/<operation.*>(.*)<\/operation>/", $requestXml, $operationMatches);
        $operation = $operationMatches[1];

        preg_match('/<operation subtype=\"(.*)">(.*)<\/operation>/', $requestXml, $operationSubtypeMatches);
        $operationSubtype = $operationSubtypeMatches[1] ?: 'N/A';

        preg_match("/<transaction-id>(.*)<\/transaction-id>/", $requestXml, $transactionMatches);
        $transactionId = $transactionMatches[1] ?: 'N/A';

        preg_match("/<transaction-id>(.*)<\/transaction-id>/", $responseXml, $transactionMatchesResponse);
        $transactionId = $transactionId == 'N/A' && $transactionMatchesResponse[1] ? $transactionMatchesResponse[1] : $transactionId;

        $requestXml = preg_replace("/<owner>(.*)<\/owner>/", '<owner>xxxxxxxx</owner>', $requestXml);
        $requestXml = preg_replace("/<bank-account-number>(.*)<\/bank-account-number>/", '<bank-account-number>xxxxxxxx</bank-account-number>', $requestXml);
        $requestXml = preg_replace("/<bank-code>(.*)<\/bank-code>/", '<bank-code>xxxxxxxx</bank-code>', $requestXml);

        try {
            $log = new \RpayRatePay\Models\Log();
            $log->setVersion($version);
            $log->setOperation($operation);
            $log->setSubOperation($operationSubtype);
            $log->setTransationId($transactionId);
            $log->setRequest($requestXml);
            $log->setResponse($responseXml);

            /** @var \Shopware\Components\Model\ModelManager $em */
            $em = Shopware()->Container()->get('models');
            $em->persist($log);
            $em->flush();
        } catch (\Exception $exception) {
            Logger::singleton()->error('RatePAY was unable to log order history: ' . $exception->getMessage());
        }
    }
}
