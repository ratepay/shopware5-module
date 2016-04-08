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
     * Customer
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Customer
    {

        /**
         * @var string
         */
        private $_firstName;

        /**
         * @var string
         */
        private $_lastName;

        /**
         * @var string
         */
        private $_salutation;

        /**
         * @var string
         */
        private $_gender;

        /**
         * @var string
         */
        private $_dateOfBirth;

        /**
         * @var string
         */
        private $_companyName = null;

        /**
         *
         * @var string
         */
        private $_vatId;

        /**
         * @var string
         */
        private $_email;

        /**
         * @var string
         */
        private $_phone;

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
         */
        private $_billingAddresses;

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
         */
        private $_shippingAddresses;

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount
         */
        private $_bankAccount = null;

        /**
         * @var string
         */
        private $_nationality;

        /**
         * @var string
         */
        private $_ipAddress;

        /**
         * This function returns the value of $_firstName
         *
         * @return string
         */
        public function getFirstName()
        {
            return $this->_firstName;
        }

        /**
         * This function sets the value for $_firstName
         *
         * @param string $firstName
         */
        public function setFirstName($firstName)
        {
            $this->_firstName = $firstName;
        }

        /**
         * This function returns the value of $_lastName
         *
         * @return string
         */
        public function getLastName()
        {
            return $this->_lastName;
        }

        /**
         * This function sets the value for $_lastName
         *
         * @param string $lastName
         */
        public function setLastName($lastName)
        {
            $this->_lastName = $lastName;
        }

        /**
         * This function returns the value of $_salutation
         *
         * @return string
         */
        public function getSalutation()
        {
            return $this->_salutation;
        }

        /**
         * This function sets the value for $_salutation
         *
         * @param string $salutation
         */
        public function setSalutation($salutation)
        {
            $this->_salutation = $salutation;
        }

        /**
         * This function returns the value of $_gender
         *
         * @return string
         */
        public function getGender()
        {
            return $this->_gender;
        }

        /**
         * This function sets the value for $_gender
         *
         * @param string $gender
         */
        public function setGender($gender)
        {
            $this->_gender = $gender;
        }

        /**
         * This function returns the value of $_dateOfBirth
         *
         * @return string
         */
        public function getDateOfBirth()
        {
            return $this->_dateOfBirth;
        }

        /**
         * This function sets the value for $_dateOfBirth
         *
         * @param string $dateOfBirth
         */
        public function setDateOfBirth($dateOfBirth)
        {
            $this->_dateOfBirth = $dateOfBirth;
        }

        /**
         * This function returns the value of $_companyName
         *
         * @return string
         */
        public function getCompanyName()
        {
            return $this->_companyName;
        }

        /**
         * This function sets the value for $_companyName
         *
         * @param string $companyName
         */
        public function setCompanyName($companyName)
        {
            $this->_companyName = $companyName;
        }

        /**
         * This function returns the value of $_email
         *
         * @return string
         */
        public function getEmail()
        {
            return $this->_email;
        }

        /**
         * This function sets the value for $_email
         *
         * @param string $email
         */
        public function setEmail($email)
        {
            $this->_email = $email;
        }

        /**
         * This function returns the value of $_phone
         *
         * @return string
         */
        public function getPhone()
        {
            return $this->_phone;
        }

        /**
         * This function sets the value for $_phone
         *
         * @param string $phone
         */
        public function setPhone($phone)
        {
            $this->_phone = $phone;
        }

        /**
         * This function returns the value of $_billingAddresses
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
         */
        public function getBillingAddresses()
        {
            return $this->_billingAddresses;
        }

        /**
         * This function sets the value for $_billingAddresses
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address $billingAddresses
         */
        public function setBillingAddresses(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address $billingAddresses)
        {
            $this->_billingAddresses = $billingAddresses;
        }

        /**
         * This function returns the value of $_shippingAddresses
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
         */
        public function getShippingAddresses()
        {
            return $this->_shippingAddresses;
        }

        /**
         * This function sets the value for $_shippingAddresses
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address $shippingAddresses
         */
        public function setShippingAddresses(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address $shippingAddresses)
        {
            $this->_shippingAddresses = $shippingAddresses;
        }

        /**
         * This function returns the value of $_bankAccount
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount
         */
        public function getBankAccount()
        {
            return $this->_bankAccount;
        }

        /**
         * This function sets the value for $_bankAccount
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount $bankAccount
         */
        public function setBankAccount(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_BankAccount $bankAccount)
        {
            $this->_bankAccount = $bankAccount;
        }

        /**
         * This function returns the value of $_nationality
         *
         * @return string
         */
        public function getNationality()
        {
            return $this->_nationality;
        }

        /**
         * This function sets the value for $_nationality
         *
         * @param string $nationality
         */
        public function setNationality($nationality)
        {
            $this->_nationality = $nationality;
        }

        /**
         * This function returns the value of $_vatId
         *
         * @return string
         */
        public function getVatId()
        {
            return $this->_vatId;
        }

        /**
         * This function sets the value for $_vatId
         *
         * @param string $vatId
         */
        public function setVatId($vatId)
        {
            $this->_vatId = $vatId;
        }

        /**
         * This function returns the value of $_ipAddress
         *
         * @return string
         */
        public function getIpAddress()
        {
            return $this->_ipAddress;
        }

        /**
         * This function sets the value for $_ipAddress
         *
         * @param string $ipAddress
         */
        public function setIpAddress($ipAddress)
        {
            $this->_ipAddress = $ipAddress;
        }


        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                'first-name'                    => $this->getFirstName(),
                'last-name'                     => $this->getLastName(),
                'salutation'                    => $this->getSalutation(),
                'gender'                        => $this->getGender(),
                'ip-address'                    => $this->getIpAddress(),
                'contacts'                      => array(
                    'email' => $this->getEmail(),
                    'phone' => array(
                        'direct-dial' => $this->getPhone()
                    )
                ),
                'addresses'                     => array(
                    0 => array(
                        'address' => $this->getBillingAddresses()->toArray(),
                    ),
                    1 => array(
                        'address' => $this->getShippingAddresses()->toArray(),
                    )
                ),
                'customer-allow-credit-inquiry' => 'yes'
            );

            if ($this->_companyName != null && $this->_vatId != null) {
                $return['company-name'] = $this->getCompanyName();
                $return['vat-id'] = $this->getVatId();
            } else {
                $return['date-of-birth'] = $this->getDateOfBirth();
            }

            if ($this->_bankAccount != null) {
                $return['bank-account'] = $this->getBankAccount()->toArray();
            }

            return $return;
        }

    }
