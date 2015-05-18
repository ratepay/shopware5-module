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
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit
    {

        /**
         * @var Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Head
         */
        private $_head;

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
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            return array(
                '@version' => '1.0',
                '@xmlns'   => "urn://www.ratepay.com/payment/1_0",
                'head'     => $this->getHead()->toArray()
            );
        }

    }
