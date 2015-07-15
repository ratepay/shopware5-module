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
     * Item
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item
    {

        /**
         * @var string
         */
        private $_articleNumber;

        /**
         * @var string
         */
        private $_articleName;

        /**
         * @var string
         */
        private $_quantity;

        /**
         * @var string
         */
        private $_taxRate;

        /**
         * @var string
         */
        private $_unitPriceGross;

        /**
         * This function returns the value of $_articleName
         *
         * @return string
         */
        public function getArticleName()
        {
            return $this->_articleName;
        }

        /**
         * This function sets the value for $_articleName
         *
         * @param string $articleName
         */
        public function setArticleName($articleName)
        {
            $this->_articleName = $articleName;
        }

        /**
         * This function returns the value of $_articleName
         *
         * @return string
         */
        public function getArticleNumber()
        {
            return $this->_articleNumber;
        }

        /**
         * This function sets the value for $_articleNumber
         *
         * @param string $articleNumber
         */
        public function setArticleNumber($articleNumber)
        {
            $this->_articleNumber = $articleNumber;
        }

        /**
         * This function returns the value of $_quantity
         *
         * @return string
         */
        public function getQuantity()
        {
            return $this->_quantity;
        }

        /**
         * This function sets the value for $_quantity
         *
         * @param string $quantity
         */
        public function setQuantity($quantity)
        {
            $this->_quantity = $quantity;
        }

        /**
         * This function returns the value of $_taxRate
         *
         * @return string
         */
        public function getTaxRate()
        {
            return $this->_taxRate;
        }

        /**
         * This function sets the value for $_taxRate
         *
         * @param string $taxRate
         */
        public function setTaxRate($taxRate)
        {
            $this->_taxRate = number_format((float)$taxRate, 2, '.', '');
        }

        /**
         * This function returns the value of $_unitPriceGross
         *
         * @return string
         */
        public function getUnitPriceGross()
        {
            return $this->_unitPriceGross;
        }

        /**
         * This function sets the value for $_unitPriceGross
         *
         * @param string $unitPriceGross
         */
        public function setUnitPriceGross($unitPriceGross)
        {
            $this->_unitPriceGross = number_format((float)$unitPriceGross, 2, '.', '');
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            return array(
                '%item' => array(
                    '@article-number'   => $this->getArticleNumber(),
                    '@quantity'         => $this->getQuantity(),
                    '@tax-rate'         => $this->getTaxRate(),
                    '@unit-price-gross' => $this->getUnitPriceGross(),
                    '#'                 => $this->getArticleName()
                )
            );
        }

    }
