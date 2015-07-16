<?php

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
            $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
            $version = Shopware()->Plugins()->Frontend()->RpayRatePay()->getVersion();

            preg_match("/<operation.*>(.*)<\/operation>/", $requestXml, $operationMatches);
            $operation = $operationMatches[1];

            preg_match('/<operation subtype=\"(.*)">(.*)<\/operation>/', $requestXml, $operationSubtypeMatches);
            $operationSubtype = $operationSubtypeMatches[1] ? : 'N/A';

            preg_match("/<transaction-id>(.*)<\/transaction-id>/", $requestXml, $transactionMatches);
            $transactionId = $transactionMatches[1] ? : 'N/A';

            preg_match("/<transaction-id>(.*)<\/transaction-id>/", $responseXml, $transactionMatchesResponse);
            $transactionId = $transactionId == 'N/A' && $transactionMatchesResponse[1] ? $transactionMatchesResponse[1] : $transactionId;

            $requestXml = preg_replace("/<owner>(.*)<\/owner>/", "<owner>xxxxxxxx</owner>", $requestXml);
            $requestXml = preg_replace("/<bank-account-number>(.*)<\/bank-account-number>/", "<bank-account-number>xxxxxxxx</bank-account-number>", $requestXml);
            $requestXml = preg_replace("/<bank-code>(.*)<\/bank-code>/", "<bank-code>xxxxxxxx</bank-code>", $requestXml);

            $bind = array(
                'version'       => $version,
                'operation'     => $operation,
                'suboperation'  => $operationSubtype,
                'transactionId' => $transactionId,
                'request'       => $requestXml,
                'response'      => $responseXml
            );

            try {
                Shopware()->Db()->insert('rpay_ratepay_logging', $bind);
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error('Fehler beim Loggen: ' . $exception->getMessage());
            }
        }

    }
