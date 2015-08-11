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
     * Head
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head
    {

        /**
         * @var string
         */
        private $_systemId;

        /**
         * @var string
         */
        private $_transactionId = null;

        /**
         * @var string
         */
        private $_profileId;

        /**
         * @var string
         */
        private $_securityCode;

        /**
         * @var string
         */
        private $_operation;

        /**
         * @var string
         */
        private $_operationSubstring = null;

        /**
         * @var string
         */
        private $_systemVersion;

        /**
         * @var string
         */
        private $_orderId = null;

        /**
         * @var string
         */
        private $_merchantConsumerId = null;

        /**
         * @var string
         */
        private $_deviceToken;

        /**
         * This function returns the value of $_systemId
         *
         * @return string
         */
        public function getSystemId()
        {
            return $this->_systemId;
        }

        /**
         * This function sets the value for $_systemId
         *
         * @param string $systemId
         */
        public function setSystemId($systemId)
        {
            $this->_systemId = $systemId;
        }

        /**
         * This function returns the value of $_transactionId
         *
         * @return string
         */
        public function getTransactionId()
        {
            return $this->_transactionId;
        }

        /**
         * This function sets the value for $_transactionId
         *
         * @param string $transactionId
         */
        public function setTransactionId($transactionId)
        {
            $this->_transactionId = $transactionId;
        }

        /**
         * This function returns the value of $_profileId
         *
         * @return string
         */
        public function getProfileId()
        {
            return $this->_profileId;
        }

        /**
         * This function sets the value for $_profileId
         *
         * @param string $profileId
         */
        public function setProfileId($profileId)
        {
            $this->_profileId = $profileId;
        }

        /**
         * This function returns the value of $_securityCode
         *
         * @return string
         */
        public function getSecurityCode()
        {
            return $this->_securityCode;
        }

        /**
         * This function sets the value for $_securityCode
         *
         * @param string $securityCode
         */
        public function setSecurityCode($securityCode)
        {
            $this->_securityCode = $securityCode;
        }

        /**
         * This function returns the value of $_operation
         *
         * @return string
         */
        public function getOperation()
        {
            return $this->_operation;
        }

        /**
         * This function sets the value for $_operation
         *
         * @param string $operation
         */
        public function setOperation($operation)
        {
            $this->_operation = $operation;
        }

        /**
         * This function returns the value of $_operationSubstring
         *
         * @return string
         */
        public function getOperationSubstring()
        {
            return $this->_operationSubstring;
        }

        /**
         * This function sets the value for $_operationSubstring
         *
         * @param string $operationSubstring
         */
        public function setOperationSubstring($operationSubstring)
        {
            $this->_operationSubstring = $operationSubstring;
        }

        /**
         * This function returns the value of $_systemVersion
         *
         * @return string
         */
        public function getSystemVersion()
        {
            return $this->_systemVersion;
        }

        /**
         * This function sets the value for $_systemVersion
         *
         * @param string $systemVersion
         */
        public function setSystemVersion($systemVersion)
        {
            $this->_systemVersion = $systemVersion;
        }

        /**
         * This function returns the value of $_orderId
         *
         * @return string
         */
        public function getOrderId()
        {
            return $this->_orderId;
        }

        /**
         * This function sets the value for $_orderId
         *
         * @param string $orderId
         */
        public function setOrderId($orderId)
        {
            $this->_orderId = $orderId;
        }

        /**
         * This function returns the value of $_merchantConsumerId
         *
         * @return string
         */
        public function getMerchantConsumerId()
        {
            return $this->_merchantConsumerId;
        }

        /**
         * This function sets the value for $_merchantConsumerId
         *
         * @param string $merchantConsumerId
         */
        public function setMerchantConsumerId($merchantConsumerId)
        {

            $this->_merchantConsumerId = $merchantConsumerId;
        }

        /**
         * @return string
         */
        public function getDeviceToken()
        {
            return $this->_deviceToken;
        }

        /**
         * @param $deviceToken
         */
        public function setDeviceToken($deviceToken)
        {

            $this->_deviceToken = $deviceToken;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {

            $return = array(
                'system-id'  => $this->getSystemId(),
                'operation'  => $this->getOperation(),
                'credential' => array(
                    'profile-id'   => $this->getProfileId(),
                    'securitycode' => $this->getSecurityCode()
                ),
                'meta'       => array(
                    'systems' => array(
                        'system' => array(
                            '@name'    => 'Shopware',
                            '@version' => $this->getSystemVersion()
                        )
                    )
                )
            );
            if (null !== $this->_orderId) {
                $return['external']['order-id'] = $this->getOrderId();
            }
            if ($this->_merchantConsumerId ==! null) {
                $return['external']['merchant-consumer-id'] = $this->getMerchantConsumerId();
            }
            if ($this->_deviceToken ==! null) {
                $return['customer-device']['device-token'] = $this->getDeviceToken();
            }
            if ($this->_transactionId != null) {
                $return['transaction-id'] = $this->getTransactionId();
            }
            if ($this->_operationSubstring != null) {
                $return['operation'] = array(
                    '@subtype' => $this->getOperationSubstring(),
                    $this->getOperation()
                );
            }

            return $return;
        }

    }
