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
     * paymentInitModel
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery
    {

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head
         */
        private $_head;

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing
         */
        private $_invoicing;

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket
         */
        private $_shoppingBasket;

        /**
         * This function returns the value of $_head
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head
         */
        public function getHead()
        {
            return $this->_head;
        }

        /**
         * This function sets the value for $_head
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head $head
         */
        public function setHead(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head $head)
        {
            $this->_head = $head;
        }

        /**
         * This function returns the value of $_invoicing
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing
         */
        public function getInvoicing()
        {
            return $this->_invoicing;
        }

        /**
         * This function sets the value for _invoicing
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing $invoicing
         */
        public function setInvoicing(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing $invoicing)
        {
            $this->_invoicing = $invoicing;
        }

        /**
         * This function returns the value of $_shoppingBasket
         *
         * @return Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket
         */
        public function getShoppingBasket()
        {
            return $this->_shoppingBasket;
        }

        /**
         * This function sets the value for $_shoppingBasket
         *
         * @param Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket $shoppingBasket
         */
        public function setShoppingBasket(Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket $shoppingBasket)
        {
            $this->_shoppingBasket = $shoppingBasket;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                '@version' => '1.0',
                '@xmlns'   => "urn://www.ratepay.com/payment/1_0",
                'head'     => $this->getHead()->toArray()
            );
            if (!is_null($this->getInvoicing())) {
                $return['content']['invoicing'] = $this->getInvoicing()->toArray();
            }
            $return['content']['shopping-basket'] = $this->getShoppingBasket()->toArray();
            return $return;
        }

    }
