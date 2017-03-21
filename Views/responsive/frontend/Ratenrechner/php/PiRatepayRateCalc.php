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
    require_once 'pi_ratepay_xml_service.php';

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
            $this->ratepay = new pi_ratepay_xml_service($this->getLive());
        }

        /**
         * This method send's the conig request to RatePAY or set's a error message
         * and returns the config details
         *
         * @return array $installmentConfigArray
         */
        public function getRatepayRateConfig()
        {
            $shopId = Shopware()->Shop()->getId();
            $userId = Shopware()->Session()->sUserId;
            $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->findOneBy(array('customerId' => $userId));
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getCountryId());
            $countryIso = $country->getIso();

            //get ratepay config based on shopId
            $rpRateConfig=Shopware()->Db()->fetchRow('
                SELECT
                `month-allowed`, `rate-min-normal`, `interestrate-default`, `payment-firstday`
                FROM
                `rpay_ratepay_config`
                WHERE
                `shopId` = ' . $shopId . '
                AND 
                `country-code-billing` LIKE \'%' . $countryIso . '%\'
            ');

            $installmentConfigArray = array(
                'interestrate_default' => $rpRateConfig["interestrate-default"],
                'month_allowed' => $rpRateConfig["month-allowed"],
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
            $this->setRequestOperation('CALCULATION_REQUEST');
            $this->setRequestOperationSubtype($subtype);
            $request = $this->ratepay->getXMLObject();

            $this->setRatepayHead($request);
            $this->setRatepayContentCalculation($request);
            $response = $this->ratepay->paymentOperation($request);
            $request_reason_msg = 'serveroff';

            if ($response) {

                $response_result_code = (string)$response->head->processing->result->attributes()->code;
                $response_reason_code = (string)$response->head->processing->reason->attributes()->code;
                $response_status_code = (string)$response->head->processing->status->attributes()->code;
                $success_codes = array('603', '671', '688', '689', '695', '696', '697', '698', '699');
                if ($response_result_code == '502' && in_array($response_reason_code, $success_codes) && $response_status_code == 'OK') {

                    $total_amount = (string)$response->content->{'installment-calculation-result'}->{'total-amount'};
                    $amount = (string)$response->content->{'installment-calculation-result'}->{'amount'};
                    $interest_rate = (string)$response->content->{'installment-calculation-result'}->{'interest-rate'};
                    $interest_amount = (string)$response->content->{'installment-calculation-result'}->{'interest-amount'};
                    $service_charge = (string)$response->content->{'installment-calculation-result'}->{'service-charge'};
                    $annual_percentage_rate = (string)$response->content->{'installment-calculation-result'}->{'annual-percentage-rate'};
                    $monthly_debit_interest = (string)$response->content->{'installment-calculation-result'}->{'monthly-debit-interest'};
                    $number_of_rates = (string)$response->content->{'installment-calculation-result'}->{'number-of-rates'};
                    $rate = (string)$response->content->{'installment-calculation-result'}->{'rate'};
                    $last_rate = (string)$response->content->{'installment-calculation-result'}->{'last-rate'};
                    $payment_firstday = (string)$response->content->{'installment-calculation-result'}->{'payment-firstday'};

                    $this->setDetailsTotalAmount($total_amount);
                    $this->setDetailsAmount($amount);
                    $this->setDetailsInterestRate($interest_rate);
                    $this->setDetailsInterestAmount($interest_amount);
                    $this->setDetailsServiceCharge($service_charge);
                    $this->setDetailsAnnualPercentageRate($annual_percentage_rate);
                    $this->setDetailsMonthlyDebitInterest($monthly_debit_interest);
                    $this->setDetailsNumberOfRates($number_of_rates);
                    $this->setDetailsRate($rate);
                    $this->setDetailsLastRate($last_rate);
                    $this->setDetailsPaymentFirstday($payment_firstday);

                    $request_reason_msg = (string)$response->head->processing->reason;
                    $this->setMsg($request_reason_msg);
                    $this->setCode($response_reason_code);
                    $this->setErrorMsg('');

                }
                else {
                    $this->setMsg('');
                    $request_reason_msg = (string)$response->head->processing->reason;
                    $this->emptyDetails();
                    throw new Exception($request_reason_msg);
                }
            }
            else {
                $this->setMsg('');
                $this->emptyDetails();
                throw new Exception($request_reason_msg);
            }
        }

        /**
         * This method set's the head element of the request xml
         */
        private function setRatepayHead($request)
        {
            $head = $request->addChild('head');

            $head->addChild('system-id', $this->getRequestSystemId());

            if ($this->getRequestTransactionId() != "")
                $head->addChild('transaction-id', $this->getRequestTransactionId());
            if ($this->getRequestTransactionShortId() != "")
                $head->addChild('transaction-short-id', $this->getRequestTransactionShortId());

            $operation = $head->addChild('operation', $this->getRequestOperation());

            if ($this->getRequestOperationSubtype() != "")
                $operation->addAttribute('subtype', $this->getRequestOperationSubtype());

            $this->setRatepayHeadCredentials($head);
            $this->setRatepayHeadExternal($head);
        }

        /**
         * This method set's the credential element of the request xml
         */
        private function setRatepayHeadCredentials($head)
        {
            $credential = $head->addChild('credential');

            $credential->addChild('profile-id', $this->getRequestProfileId());
            $credential->addChild('securitycode', $this->getRequestSecurityCode());
        }

        /**
         * This method set's the external element of the request xml
         */
        private function setRatepayHeadExternal($head)
        {
            if ($this->getRequestOrderId() != "" || $this->getRequestMerchantConsumerId() != "" || $this->getRequestMerchantConsumerClassification() != "") {
                $external = $head->addChild('external');

                if ($this->getRequestOrderId() != "")
                    $external->addChild('order-id', $this->getRequestOrderId());
                if ($this->getRequestMerchantConsumerId() != "")
                    $external->addChild('merchant-consumer-id', $this->getRequestMerchantConsumerId());
                if ($this->getRequestMerchantConsumerClassification() != "")
                    $external->addChild('merchant-consumer-classification', $this->getRequestMerchantConsumerClassification());
            }
        }

        /**
         * This method set's the installment-calculation element of the request xml
         */
        private function setRatepayContentCalculation($request)
        {
            $content = $request->addChild('content');
            $installment = $content->addChild('installment-calculation');

            if ($this->getRequestInterestRate() != "") {
                $configuration = $installment->addChild('configuration');
                $configuration->addChild('interest-rate', $this->getRequestInterestRate());
            }

            $installment->addChild('amount', $this->getRequestAmount());

            if ($this->getRequestFirstday()) {
                $installment->addChild('payment-firstday', $this->getRequestFirstday());
            }

            if ($this->getRequestOperationSubtype() == 'calculation-by-rate') {
                $calc_rate = $installment->addChild('calculation-rate');
                $calc_rate->addChild('rate', $this->getRequestCalculationValue());
            }
            else if ($this->getRequestOperationSubtype() == 'calculation-by-time') {
                $calc_time = $installment->addChild('calculation-time');
                $calc_time->addChild('month', $this->getRequestCalculationValue());
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
