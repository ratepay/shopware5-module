<?php

    /**
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     *
     * @package pi_ratepay_rate_calculator
     * Code by Ratepay GmbH  <http://www.ratepay.com/>
     */
    require_once 'PiRatepayRateCalcBase.php';

    /**
     * This is for the communication with RatePAY
     */
    class PiRatepayRateCalc extends PiRatepayRateCalcBase
    {

        //SimpleXML Object
        private $ratepay;

        //Installment Details

        /**
         * This constructor set's the simple xml object
         */
        public function PiRatepayRateCalc()
        {
            parent::PiRatepayRateCalcBase();
        }

        /**
         * This method send's the conig request to RatePAY or set's a error message
         * and returns the config details
         *
         * @return array $installmentConfigArray
         */
        public function getRatepayRateConfig($backend = false)
        {
            $paymentType = $_SESSION['Shopware']['sOrderVariables']['sUserData']['additional']['payment']['name'];
            if ($paymentType == 'rpayratepayrate') {
                $paymentType = 'installment';
            } elseif ($paymentType == 'rpayratepayrate0') {
                $paymentType = 'installment0';
            }

            $shopId = Shopware()->Shop()->getId();
            $userId = Shopware()->Session()->sUserId;
            $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->findOneBy(array('customerId' => $userId));
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getCountryId());
            $countryIso = $country->getIso();
            $basketAmount = $this->getRequestAmount();

            $qry = "SELECT rrci.`month-allowed`, rrci.`rate-min-normal`, rrci.`interestrate-default`, rrci.`payment-firstday`
                    FROM `rpay_ratepay_config_installment` AS rrci
                      JOIN `rpay_ratepay_config` AS rrc
                        ON rrci.`rpay_id` = rrc.`" . $paymentType . "`
                    WHERE rrc.`shopId` = " . $shopId . "
                    AND rrc.`country-code-billing` LIKE '%" . $countryIso . "%'";

            //get ratepay config based on shopId
            $rpRateConfig=Shopware()->Db()->fetchRow($qry);

            $interestRate = ((float)$rpRateConfig["interestrate-default"] / 12) / 100;
            $monthAllowed = explode(',', $rpRateConfig["month-allowed"]);

            foreach ($monthAllowed AS $month) {
                $rateAmount = ceil($basketAmount * (($interestRate * pow((1 + $interestRate), $month)) / (pow((1 + $interestRate), $month) - 1)));
                if($rateAmount >= $rpRateConfig["rate-min-normal"]) {
                    $allowedRuntimes[] = $month;
                }
            }

            $installmentConfigArray = array(
                'interestrate_default' => $rpRateConfig["interestrate-default"],
                'month_allowed' => $allowedRuntimes,
                'rate_min_normal' => $rpRateConfig["rate-min-normal"],
                'payment_firstday' => $rpRateConfig["payment-firstday"],
            );

            return $installmentConfigArray;
        }

        /**
         * This method send's the rate request to RatePAY or set's a error message
         * and returns the rate details
         *
         * @return array $resultArray
         */
        public function getRatepayRateDetails($subtype)
        {
            try {
                $this->requestRateDetails($subtype);
                $this->setData($this->getDetailsTotalAmount(), $this->getDetailsAmount(), $this->getDetailsInterestRate(), $this->getDetailsInterestAmount(), $this->getDetailsServiceCharge(), $this->getDetailsAnnualPercentageRate(), $this->getDetailsMonthlyDebitInterest(), $this->getDetailsNumberOfRates(), $this->getDetailsRate(), $this->getDetailsLastRate(), $this->getDetailsPaymentFirstday());
            } catch (Exception $e) {
                $this->unsetData();
                $this->setErrorMsg($e->getMessage());
            }

            return $this->createFormattedResult();
        }

        /**
         * Return the formatted Results
         *
         * @return array $resultArray
         */
        public function createFormattedResult()
        {
            if ($this->getLanguage() == 'DE') {
                $currency = '&euro;';
                $decimalSeperator = ',';
                $thousandSepeartor = '.';
            }
            else {
                $currency = '&euro;';
                $decimalSeperator = '.';
                $thousandSepeartor = ',';
            }

            $resultArray = array();
            $resultArray['totalAmount'] = number_format((double)$this->getDetailsTotalAmount(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['amount'] = number_format((double)$this->getDetailsAmount(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['interestAmount'] = number_format((double)$this->getDetailsInterestAmount(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['serviceCharge'] = number_format((double)$this->getDetailsServiceCharge(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['interestRate'] = number_format((double) $this->getDetailsInterestRate(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['annualPercentageRate'] = number_format((double)$this->getDetailsAnnualPercentageRate(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['monthlyDebitInterest'] = number_format((double)$this->getDetailsMonthlyDebitInterest(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['numberOfRatesFull'] = (int)$this->getDetailsNumberOfRates();
            $resultArray['numberOfRates'] = (int)$this->getDetailsNumberOfRates() - 1;
            $resultArray['rate'] = number_format((double)$this->getDetailsRate(), 2, $decimalSeperator, $thousandSepeartor);
            $resultArray['lastRate'] = number_format((double)$this->getDetailsLastRate(), 2, $decimalSeperator, $thousandSepeartor);

            return $resultArray;
        }

        /**
         * This method send the rate request to RatePAY and set's all response data
         * if a error occurs the mthod throws a exception
         */
        private function requestRateDetails($subtype)
        {
            if (isset(Shopware()->Session()->sUserId)) {
                $userId = Shopware()->Session()->sUserId;
            } elseif (isset($Parameter['userid'])) {
                $userId = $Parameter['userid'];
            } else { // return if no current user set. e.g. call by crawler
                return "RatePAY frontend controller: No user set";
            }

            $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

            if (isset($Parameter['checkoutBillingAddressId']) && !is_null($Parameter['checkoutBillingAddressId'])) { // From Shopware 5.2 current billing address is sent by parameter
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $customerAddressBilling = $addressModel->findOneBy(array('id' => $Parameter['checkoutBillingAddressId']));
                $country = $customerAddressBilling->getCountry();
            } else {
                $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->findOneBy(array('customerId' => $userId));
                $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getCountryId());
            }
            
            $modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();

            $user = Shopware()->Session()->sOrderVariables['sUserData'];
            if (!empty($user['additional']['payment']['name'])) {
                if ($user['additional']['payment']['name'] == 'rpayratepayrate0') {
                    $modelFactory->setZPercent();
                }
            }

            $operationData['payment']['amount'] = $this->getRequestAmount();
            $operationData['payment']['paymentFirstday'] = $this->getRequestFirstday();
            $operationData['payment']['month'] = $this->getRequestCalculationValue();
            $operationData['payment']['rate'] = $this->getRequestCalculationValue();
            $operationData['subtype'] = $subtype;

            $result = $modelFactory->callRequest('CalculationRequest', $operationData);

            if ($result->isSuccessful()) {
                    $resultArray = $result->getResult();
                    if ($subtype == 'calculation-by-time') {
                        if ($this->getRequestCalculationValue() == $resultArray['numberOfRatesFull']) {
                            $reasonCode = 603;
                        } else {
                            $reasonCode = 671;
                        }
                    } else {
                        if ($this->getRequestCalculationValue() == $resultArray['rate']) {
                            $reasonCode = 603;
                        } else {
                            $reasonCode = 697;
                        }
                    }

                    $this->setDetailsTotalAmount($result->getPaymentAmount());
                    $this->setDetailsAmount($this->getRequestAmount());
                    $this->setDetailsInterestRate($result->getInterestRate());
                    $this->setDetailsInterestAmount($resultArray['interestAmount']);
                    $this->setDetailsServiceCharge($resultArray['serviceCharge']);
                    $this->setDetailsAnnualPercentageRate($resultArray['annualPercentageRate']);
                    $this->setDetailsMonthlyDebitInterest($resultArray['monthlyDebitInterest']);
                    $this->setDetailsNumberOfRates($resultArray['numberOfRatesFull']);
                    $this->setDetailsRate($resultArray['rate']);
                    $this->setDetailsLastRate($resultArray['lastRate']);
                    $this->setDetailsPaymentFirstday($result->getPaymentFirstday());

                    $this->setMsg($result->getReasonMessage());
                    $this->setCode($reasonCode);
                    $this->setErrorMsg('');
            }
            else {
                $this->setMsg('');
                $this->emptyDetails();
                throw new Exception($result->getReasonMessage());
            }
        }

        /**
         * This method set's the complete details to an empty string
         */
        private function emptyDetails()
        {
            $this->setDetailsTotalAmount('');
            $this->setDetailsAmount('');
            $this->setDetailsInterestAmount('');
            $this->setDetailsServiceCharge('');
            $this->setDetailsAnnualPercentageRate('');
            $this->setDetailsMonthlyDebitInterest('');
            $this->setDetailsNumberOfRates('');
            $this->setDetailsRate('');
            $this->setDetailsLastRate('');
            $this->setDetailsPaymentFirstday('');
        }

    }
