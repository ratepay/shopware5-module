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
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing
    {

        /**
         * @var string
         */
        private $_invoiceId;

        /**
         * @var string
         */
        private $_invoiceDate;

        /**
         * @var string
         */
        private $_deliveryDate;

        /**
         * @var string
         */
        private $_dueDate;

        /**
         * This function returns the value of $_invoiceId
         *
         * @return string
         */
        public function getInvoiceId()
        {
            return $this->_invoiceId;
        }

        /**
         * This function sets the value for _invoiceId
         *
         * @param string $invoiceId
         */
        public function setInvoiceId($invoiceId)
        {
            $this->_invoiceId = $invoiceId;
        }

        /**
         * This function returns the value of $_invoiceDate
         *
         * @return string
         */
        public function getInvoiceDate()
        {
            return $this->_invoiceDate;
        }

        /**
         * This function sets the value for $_invoiceDate
         *
         * @param string $invoiceDate
         */
        public function setInvoiceDate($invoiceDate)
        {
            $this->_invoiceDate = $invoiceDate;
        }

        /**
         * This function returns the value of $deliveryDate
         *
         * @return string
         */
        public function getDeliveryDate()
        {
            return $this->_deliveryDate;
        }

        /**
         * This function sets the value for $deliveryDate
         *
         * @param string $deliveryDate
         */
        public function setDeliveryDate($deliveryDate)
        {
            $this->_deliveryDate = $deliveryDate;
        }

        /**
         * This function returns the value of $_dueDate
         *
         * @return string
         */
        public function getDueDate()
        {
            return $this->_dueDate;
        }

        /**
         * This function sets the value for $_dueDate
         *
         * @param string $dueDate
         */
        public function setDueDate($dueDate)
        {
            $this->_dueDate = $dueDate;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                'invoice-id'    => $this->getInvoiceId(),
                'invoice-date'  => $this->getInvoiceDate(),
                'delivery-date' => $this->getDeliveryDate()
            );
            if (!is_null($this->getDueDate())) {
                $return['due-date'] = $this->getDueDate();
            }
            return $return;
        }

    }
