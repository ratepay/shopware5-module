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
     * BankAccount
     *
     * @category   RatePAY
     * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
     ** @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount
    {

        /**
         * @var string
         */
        private $_owner;

        /**
         * @var string
         */
        private $_bankAccountNumber;

        /**
         * @var string
         */
        private $_bankCode;

        /**
         * This function returns the value of $_owner
         *
         * @return string
         */
        public function getOwner()
        {
            return $this->_owner;
        }

        /**
         * This function sets the value for $_owner
         *
         * @param string $owner
         */
        public function setOwner($owner)
        {
            $this->_owner = $owner;
        }

        /**
         * This function returns the value of $_bankAccount
         *
         * @return string
         */
        public function getBankAccount()
        {
            return $this->_bankAccountNumber;
        }

        /**
         * This function sets the value for $_bankAccount
         *
         * @param string $bankAccount
         */
        public function setBankAccount($bankAccountNumber)
        {
            $this->_bankAccountNumber = $bankAccountNumber;
        }

        /**
         * This function returns the value of $_bankCode
         *
         * @return string
         */
        public function getBankCode()
        {
            return $this->_bankCode;
        }

        /**
         * This function sets the value for $_bankCode
         *
         * @param string $bankCode
         */
        public function setBankCode($bankCode)
        {
            $this->_bankCode = $bankCode;
        }

        /**
         * This function returns all values as Array
         * This contains a quickfix for sepa transactions
         *
         * @toDo: real implementation for sepa elv
         *
         * @return array
         */
        public function toArray()
        {

            if (false !== strpos(strtolower($this->getBankAccount()), 'de')) {

                return array(
                    'owner'     => $this->getOwner(),
                    'iban'      => $this->getBankAccount()
                );

            } elseif(false !== strpos(strtolower($this->getBankAccount()), 'at')) {

                return array(
                    'owner'     => $this->getOwner(),
                    'iban'      => $this->getBankAccount(),
                    'bic-swift' => $this->getBankCode()
                );

            }

            return array(
                'owner'               => $this->getOwner(),
                'bank-account-number' => $this->getBankAccount(),
                'bank-code'           => $this->getBankCode()
            );
        }

    }
