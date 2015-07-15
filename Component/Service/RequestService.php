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
     * RequestService
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Service_RequestService
    {

        private $_zendHttpClient;
        private $_logging;
        protected $config = array(
            'strictredirects' => false,
            'adapter'         => 'Zend_Http_Client_Adapter_Curl',
            'curloptions'     => array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST           => 1,
                #CURLOPT_SSLVERSION     => 6,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER     => array(
                    "Content-Type: text/xml; charset=UTF-8",
                    "Accept: */*",
                    "Cache-Control: no-cache",
                    "Pragma: no-cache",
                    "Connection: keep-alive"
                )
            ),
            'maxredirects'    => 5,
            'useragent'       => 'Zend_Http_Client',
            'timeout'         => 10,
            'httpversion'     => Zend_Http_Client::HTTP_1,
            'keepalive'       => false,
            'storeresponse'   => true,
            'strict'          => true,
            'output_stream'   => false,
            'encodecookies'   => true,
            'rfc3986_strict'  => false
        );

        /**
         * Initiate the Object
         *
         * @param boolean $sandbox
         */
        public function __construct($sandbox)
        {
            $uri = $sandbox ? 'https://gateway-int.ratepay.com/api/xml/1_0' : 'https://gateway.ratepay.com/api/xml/1_0';

            $this->_zendHttpClient = new Zend_Http_Client($uri, $this->config);
            $this->_logging = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();
        }

        /**
         * Sends an XML-Request
         *
         * @param mixed $Model
         *
         * @return \DOMDocument
         */
        public function xmlRequest($Model)
        {
            $xml = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::convertToXml($Model, 'request');
            $this->_zendHttpClient->setRawData(trim($xml->asXML(), "\xef\xbb\xbf"), "text/xml; charset=UTF-8");
            $result = $this->_zendHttpClient->request('POST');
            $dom = new DOMDocument();
            $dom->loadXML($result->getBody());
            $this->_logging->logRequest($this->getLastRequest(), $this->getLastResponse());

            return $dom;
        }

        /**
         * Returns the last Response
         *
         * @return Zend_Http_Response
         */
        public function getLastResponse()
        {
            return $this->_zendHttpClient->getLastResponse();
        }

        /**
         * Returns the last Request
         *
         * @return string
         */
        public function getLastRequest()
        {
            return $this->_zendHttpClient->getLastRequest();
        }

    }
