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
     * ShippingBasket
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket
    {

        /**
         * @var string
         */
        private $_amount;

        /**
         * @var string
         */
        private $_currency;

        /**
         * @var array
         */
        private $_items;

        /**
         * This function returns the value of $_amount
         *
         * @return string
         */
        public function getAmount()
        {
            return $this->_amount;
        }

        /**
         * This function sets the value for $_amount
         *
         * @param string $amount
         */
        public function setAmount($amount)
        {
            $this->_amount = number_format((float)$amount, 2, '.', '');;
        }

        /**
         * This function returns the value of $_currency
         *
         * @return string
         */
        public function getCurrency()
        {
            return $this->_currency;
        }

        /**
         * This function sets the value for $_currency
         *
         * @param string $currency
         */
        public function setCurrency($currency)
        {
            $this->_currency = $currency;
        }

        /**
         * This function returns the value of $_items
         *
         * @return array
         */
        public function getItems()
        {
            return $this->_items;
        }

        /**
         * This function sets the value for $_items
         *
         * @param array $items
         */
        public function setItems(array $items)
        {
            $this->_items = $items;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                '@amount'   => $this->getAmount(),
                '@currency' => $this->getCurrency()
            );

            $return['items'] = array();

            foreach ($this->getItems() as $item) {
                $return['items'][] = $item->toArray();
            }

            return $return;
        }

    }
