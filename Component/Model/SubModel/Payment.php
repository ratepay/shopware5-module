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
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Payment
    {

        /**
         * @var string
         */
        private $_method;

        /**
         * @var string
         */
        private $_currency;

        /**
         * @var float
         */
        private $_amount;

        /**
         * @var integer
         */
        private $_installmentNumber;

        /**
         * @var float
         */
        private $_installmentAmount;

        /**
         * @var float
         */
        private $_lastInstallmentAmount;

        /**
         * @var float
         */
        private $_interestRate;

        /**
         * @var integer
         */
        private $_paymentFirstday;

        /**
         * @var string
         */
        private $_directPayType;

        /**
         * This function returns the value of $_method
         *
         * @return string
         */
        public function getMethod()
        {
            return $this->_method;
        }

        /**
         * This function sets the value for $_method
         *
         * @param string $method
         */
        public function setMethod($method)
        {
            $this->_method = $method;
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
            $this->_amount = number_format((float)$amount, 2, '.', '');
        }

        /**
         * This function returns the value of $_installmentNumber
         *
         * @return string
         */
        public function getInstallmentNumber()
        {
            return $this->_installmentNumber;
        }

        /**
         * This function sets the value for $_installmentNumber
         *
         * @param string $installmentNumber
         */
        public function setInstallmentNumber($installmentNumber)
        {
            $this->_installmentNumber = $installmentNumber;
        }

        /**
         * This function returns the value of $_installmentAmount
         *
         * @return string
         */
        public function getInstallmentAmount()
        {
            return $this->_installmentAmount;
        }

        /**
         * This function sets the value for $_installmentAmount
         *
         * @param string $installmentAmount
         */
        public function setInstallmentAmount($installmentAmount)
        {
            $this->_installmentAmount = $installmentAmount;
        }

        /**
         * This function returns the value of $_lastInstallmentAmount
         *
         * @return string
         */
        public function getLastInstallmentAmount()
        {
            return $this->_lastInstallmentAmount;
        }

        /**
         * This function sets the value for $_lastInstallmentAmount
         *
         * @param string $lastInstallmentAmount
         */
        public function setLastInstallmentAmount($lastInstallmentAmount)
        {
            $this->_lastInstallmentAmount = $lastInstallmentAmount;
        }

        /**
         * This function returns the value of $_interestRate
         *
         * @return string
         */
        public function getInterestRate()
        {
            return $this->_interestRate;
        }

        /**
         * This function sets the value for $_interestRate
         *
         * @param string $interestRate
         */
        public function setInterestRate($interestRate)
        {
            $this->_interestRate = $interestRate;
        }

        /**
         * This function returns the value of $_paymentFirstday
         *
         * @return string
         */
        public function getPaymentFirstday()
        {
            return $this->_paymentFirstday;
        }

        /**
         * This function sets the value for $_paymentFirstday
         *
         * @param string $paymentFirstday
         */
        public function setPaymentFirstday($paymentFirstday)
        {
            $this->_paymentFirstday = $paymentFirstday;
        }

        /**
         * This function returns the value of $_directPayType
         *
         * @return string
         */
        public function getDirectPayType()
        {
            return $this->_directPayType;
        }

        /**
         * This function sets the value for $_directPayType
         *
         * @param string $directPayType
         */
        public function setDirectPayType($directPayType)
        {
            $this->_directPayType = $directPayType;
        }

        /**
         * This function returns all values as Array
         *
         * @return array
         */
        public function toArray()
        {
            $return = array(
                '@method'   => $this->getMethod(),
                '@currency' => $this->getCurrency(),
                'amount'    => $this->getAmount()
            );
            if ($return['@method'] === 'INSTALLMENT') {
                $return['installment-details'] = array(
                    'installment-number'      => $this->getInstallmentNumber(),
                    'installment-amount'      => $this->getInstallmentAmount(),
                    'last-installment-amount' => $this->getLastInstallmentAmount(),
                    'interest-rate'           => $this->getInterestRate(),
                    'payment-firstday'        => $this->getPaymentFirstday()
                );
                $return['debit-pay-type'] = $this->getDirectPayType();
            }

            return $return;
        }

    }
