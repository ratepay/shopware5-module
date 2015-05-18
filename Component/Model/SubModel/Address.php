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
     * Address
     *
     * @category   RatePAY
     * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
     ** @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Address
    {

        /**
         * @var string
         */
        private $_street;

        /**
         * @var string
         */
        private $_streetNumber;

        /**
         * @var string
         */
        private $_zipCode;

        /**
         * @var string
         */
        private $_city;

        /**
         * @var string
         */
        private $_countryCode;

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
        private $_company;

        /**
         * @var string
         */
        private $_type;

        /**
         * This function returns the value of $_type
         *
         * @return string
         */
        public function getType()
        {
            return $this->_type;
        }

        /**
         * This function sets the value for $_type
         *
         * @param string $type
         */
        public function setType($type)
        {
            $this->_type = $type;
        }

        /**
         * This function returns the value of $_street
         *
         * @return string
         */
        public function getStreet()
        {
            return $this->_street;
        }

        /**
         * This function sets the value for $_street
         *
         * @param string $street
         */
        public function setStreet($street)
        {
            $this->_street = $street;
        }

        /**
         * This function returns the value of $_streetNumber
         *
         * @return string
         */
        public function getStreetNumber()
        {
            return $this->_streetNumber;
        }

        /**
         * This function sets the value for $_streetNumber
         *
         * @param string $streetNumber
         */
        public function setStreetNumber($streetNumber)
        {
            $this->_streetNumber = (string)$streetNumber;
        }

        /**
         * This function returns the value of $_zipCode
         *
         * @return string
         */
        public function getZipCode()
        {
            return $this->_zipCode;
        }

        /**
         * This function sets the value for $_zipCode
         *
         * @param string $zipCode
         */
        public function setZipCode($zipCode)
        {
            $this->_zipCode = (string)$zipCode;
        }

        /**
         * This function returns the value of $_city
         *
         * @return string
         */
        public function getCity()
        {
            return $this->_city;
        }

        /**
         * This function sets the value for $_city
         *
         * @param string $city
         */
        public function setCity($city)
        {
            $this->_city = $city;
        }

        /**
         * This function returns the value of $_countryCode
         *
         * @return string
         */
        public function getCountryCode()
        {
            return $this->_countryCode;
        }

        /**
         * This function sets the value for $_countryCode
         *
         * @param string $countryCode
         */
        public function setCountryCode($countryCode)
        {
            $this->_countryCode = $countryCode;
        }

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
         * This function returns the value of $_company
         *
         * @return string
         */
        public function getCompany()
        {
            return $this->_company;
        }

        /**
         * This function sets the value for $_company
         *
         * @param string $company
         */
        public function setCompany($company)
        {
            $this->_company = $company;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                '@type'         => $this->getType(),
                '%street'       => $this->getStreet(),
                'street-number' => $this->getStreetNumber(),
                'zip-code'      => $this->getZipCode(),
                '%city'         => $this->getCity(),
                'country-code'  => $this->getCountryCode()
            );

            if ($this->getType() === 'DELIVERY') {
                $return['first-name'] = $this->getFirstName();
                $return['last-name'] = $this->getLastName();
                $return['salutation'] = $this->getSalutation();
                if (isset($this->_company)) {
                    $return['company'] = $this->getCompany();
                }
            }

            return $return;
        }

    }
