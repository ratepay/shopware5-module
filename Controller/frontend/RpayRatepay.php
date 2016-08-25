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
     * RpayRatepay
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    use Shopware\Components\CSRFWhitelistAware;

    class Shopware_Controllers_Frontend_RpayRatepay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
    {

        /**
         * Stores an Instance of the Shopware\Models\Customer\Billing model
         *
         * @var Shopware\Models\Customer\Billing
         */
        private $_config;
        private $_service;
        private $_modelFactory;
        private $_logging;

        /**
         * Initiates the Object
         */
        public function init()
        {
            $Parameter = $this->Request()->getParams();

            if (isset(Shopware()->Session()->sUserId)) {
                $userId = Shopware()->Session()->sUserId;
            } elseif (isset($Parameter['userid'])) {
                $userId = $Parameter['userid'];
            } else { // return if no current user set. e.g. call by crawler
                return "RatePAY frontend controller: No user set";
            }

            $this->_config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

            if (isset($Parameter['checkoutBillingAddressId']) && !is_null($Parameter['checkoutBillingAddressId'])) { // From Shopware 5.2 current billing address is sent by parameter
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $customerAddressBilling = $addressModel->findOneBy(array('id' => $Parameter['checkoutBillingAddressId']));
                $country = $customerAddressBilling->getCountry();
            } else {
                $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->findOneBy(array('customerId' => $userId));
                $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getCountryId());
            }

            //set sandbox mode based on config
            $sandbox = $this->_config->get('RatePaySandbox' . $country->getIso());

            $this->_service = new Shopware_Plugins_Frontend_RpayRatePay_Component_Service_RequestService($sandbox);

            $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            $this->_logging      = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();
        }

        /**
         *  Checks the Paymentmethod
         */
        public function indexAction()
        {
            Shopware()->Session()->ratepayErrorRatenrechner = false;
            if (preg_match("/^rpayratepay(invoice|rate|debit)$/", $this->getPaymentShortName())) {
                if ($this->getPaymentShortName() === 'rpayratepayrate' && !isset(Shopware()->Session(
                        )->RatePAY['ratenrechner'])
                ) {
                    Shopware()->Session()->ratepayErrorRatenrechner = true;
                    $this->redirect(
                        Shopware()->Front()->Router()->assemble(
                            array(
                                'controller'  => 'checkout',
                                'action'      => 'confirm',
                                'forceSecure' => true
                            )
                        )
                    );
                } else {
                    $this->_proceedPayment();
                }
            } else {
                $this->redirect(
                    Shopware()->Front()->Router()->assemble(
                        array(
                            'controller'  => 'checkout',
                            'action'      => 'confirm',
                            'forceSecure' => true
                        )
                    )
                );
            }
        }

        /**
         * Updates phone, ustid, company and the birthday for the current user.
         */
        public function saveUserDataAction()
        {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
            $Parameter = $this->Request()->getParams();

            $customerModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer');
            $userModel = $customerModel->findOneBy(array('id' => Shopware()->Session()->sUserId));

            if (isset($Parameter['checkoutBillingAddressId']) && !is_null($Parameter['checkoutBillingAddressId'])) { // From Shopware 5.2 current billing address is sent by parameter
                $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
                $customerAddressBilling = $addressModel->findOneBy(array('id' => $Parameter['checkoutBillingAddressId']));
                Shopware()->Session()->RatePAY['checkoutBillingAddressId'] = $Parameter['checkoutBillingAddressId'];
                if (isset($Parameter['checkoutShippingAddressId']) && !is_null($Parameter['checkoutShippingAddressId'])) {
                    Shopware()->Session()->RatePAY['checkoutShippingAddressId'] = $Parameter['checkoutShippingAddressId'];
                } else {
                    unset(Shopware()->Session()->RatePAY['checkoutShippingAddressId']);
                }
            } else {
                $customerAddressBilling = $userModel->getBilling();
            }

            $return = 'OK';
            $updateUserData = array();
            $updateAddressData = array();

            if (!is_null($customerAddressBilling)) {
                if (method_exists($customerAddressBilling, 'getBirthday')) {
                    $updateAddressData['phone'] = $Parameter['ratepay_phone'] ? : $customerAddressBilling->getPhone();
                    if ($customerAddressBilling->getCompany() !== "") {
                        $updateAddressData['company'] = $Parameter['ratepay_company'] ? : $customerAddressBilling->getCompany();
                        $updateAddressData['ustid'] = $Parameter['ratepay_ustid'] ? : $customerAddressBilling->getVatId();
                    } else {
                        $updateAddressData['birthday'] = $Parameter['ratepay_dob'] ? : $customerAddressBilling->getBirthday()->format("Y-m-d");
                    }

                    try {
                        Shopware()->Db()->update('s_user_billingaddress', $updateAddressData, 'userID=' . $Parameter['userid']); // ToDo: Why parameter?
                        Shopware()->Pluginlogger()->info('Kundendaten aktualisiert.');
                    } catch (Exception $exception) {
                        Shopware()->Pluginlogger()->error('Fehler beim Updaten der Userdaten: ' . $exception->getMessage());
                        $return = 'NOK';
                    }

                } elseif (method_exists($userModel, 'getBirthday')) { // From Shopware 5.2 birthday is moved to customer object
                    $updateAddressData['phone'] = $Parameter['ratepay_phone'] ? : $customerAddressBilling->getPhone();
                    if (!is_null($customerAddressBilling->getCompany())) {
                        $updateAddressData['company'] = $Parameter['ratepay_company'] ? : $customerAddressBilling->getCompany();
                        $updateAddressData['ustid'] = $Parameter['ratepay_ustid'] ? : $customerAddressBilling->getVatId();
                    } else {
                        $updateUserData['birthday'] = $Parameter['ratepay_dob'] ? : $userModel->getBirthday()->format("Y-m-d");
                    }

                    try {
                        if (count($updateUserData) > 0) {
                            Shopware()->Db()->update('s_user', $updateUserData, 'id=' . $Parameter['userid']); // ToDo: Why parameter?
                        }
                        if (count($updateAddressData) > 0) {
                            Shopware()->Db()->update('s_user_addresses', $updateAddressData, 'id=' . $Parameter['checkoutBillingAddressId']);
                        }
                        Shopware()->Pluginlogger()->info('Kundendaten aktualisiert.');
                    } catch (Exception $exception) {
                        Shopware()->Pluginlogger()->error('Fehler beim Updaten der User oder Address daten: ' . $exception->getMessage());
                        $return = 'NOK';
                    }
                } else {
                    $return = 'NOK';
                }


            }

            if ($Parameter['ratepay_debit_updatedebitdata']) {
                Shopware()->Session()->RatePAY['bankdata']['account']    = $Parameter['ratepay_debit_accountnumber'];
                Shopware()->Session()->RatePAY['bankdata']['bankcode']   = $Parameter['ratepay_debit_bankcode'];
                Shopware()->Session()->RatePAY['bankdata']['bankholder'] = $customerAddressBilling->getFirstname() . " " . $customerAddressBilling->getLastname();
            }

            echo $return;
        }

        /**
         * Procceds the whole Paymentprocess
         */
        private function _proceedPayment()
        {
            $paymentInitModel = $this->_modelFactory->getModel(
                new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentInit()
            );

            $result = $this->_service->xmlRequest($paymentInitModel->toArray());
            if (Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse(
                'PAYMENT_INIT',
                $result
            )
            ) {
                Shopware()->Session()->RatePAY['transactionId'] = $result->getElementsByTagName('transaction-id')->item(
                    0
                )->nodeValue;
                $this->_modelFactory->setTransactionId(Shopware()->Session()->RatePAY['transactionId']);
                $paymentRequestModel = $this->_modelFactory->getModel(
                    new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentRequest()
                );
                $result = $this->_service->xmlRequest($paymentRequestModel->toArray());
                if (Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse(
                    'PAYMENT_REQUEST',
                    $result
                )
                ) {
                    $uniqueId = $this->createPaymentUniqueId();
                    $orderNumber = $this->saveOrder(Shopware()->Session()->RatePAY['transactionId'], $uniqueId, 17);
                    $paymentConfirmModel = $this->_modelFactory->getModel(
                        new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentConfirm()
                    );
                    $matches = array();
                    preg_match("/<descriptor.*>(.*)<\/descriptor>/", $this->_service->getLastResponse(), $matches);
                    $dgNumber = $matches[1];
                    $result = $this->_service->xmlRequest($paymentConfirmModel->toArray());
                    if (Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse(
                        'PAYMENT_CONFIRM',
                        $result
                    )
                    ) {
                        if (Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'] > 0) {
                            $this->initShipping($orderNumber);
                        }
                        try {
                            $orderId = Shopware()->Db()->fetchOne(
                                'SELECT `id` FROM `s_order` WHERE `ordernumber`=?',
                                array($orderNumber)
                            );
                            Shopware()->Db()->update(
                                's_order_attributes',
                                array(
                                    'attribute5' => $dgNumber,
                                    'attribute6' => Shopware()->Session()->RatePAY['transactionId'],
                                ),
                                'orderID=' . $orderId
                            );
                        } catch (Exception $exception) {
                            Shopware()->Pluginlogger()->error($exception->getMessage());
                        }

                        //set payments status to payed
                        $this->savePaymentStatus(
                            Shopware()->Session()->RatePAY['transactionId'],
                            $uniqueId,
                            12
                        );

                        /**
                         * unset DFI token
                         */
                        if (Shopware()->Session()->RatePAY['dfpToken']) {
                            unset(Shopware()->Session()->RatePAY['dfpToken']);
                        }

                        /*
                         * redirect to success page
                         */
                        $this->redirect(
                            array(
                                'controller'  => 'checkout',
                                'action'      => 'finish',
                                'sUniqueID' => $uniqueId,
                                'forceSecure' => true
                            )
                        );
                    } else {
                        $this->_error();
                    }
                } else {
                    $this->_error();
                }
            } else {
                $this->_error();
            }
        }

        /**
         * Redirects the User in case of an error
         */
        private function _error()
        {
            Shopware()->Session()->RatePAY['hidePayment'] = true;

            $this->View()->loadTemplate("frontend/payment_rpay_part/RatePAYErrorpage.tpl");
        }

        /**
         * calcDesign-function for Ratenrechner
         */
        public function calcDesignAction()
        {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
            $calcPath = realpath(dirname(__FILE__) . '/../../Views/responsive/frontend/Ratenrechner/php/');
            require_once $calcPath . '/PiRatepayRateCalc.php';
            require_once $calcPath . '/path.php';
            require_once $calcPath . '/PiRatepayRateCalcDesign.php';
        }

        /**
         * calcRequest-function for Ratenrechner
         */
        public function calcRequestAction()
        {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
            $calcPath = realpath(dirname(__FILE__) . '/../../Views/responsive/frontend/Ratenrechner/php/');
            require_once $calcPath . '/PiRatepayRateCalc.php';
            require_once $calcPath . '/path.php';
            require_once $calcPath . '/PiRatepayRateCalcRequest.php';
        }

        /**
         * Initiates the Shipping-Position fo the given order
         *
         * @param string $orderNumber
         */
        private function initShipping($orderNumber)
        {
            try {
                $orderID = Shopware()->Db()->fetchOne(
                    "SELECT `id` FROM `s_order` WHERE `ordernumber`=?",
                    array($orderNumber)
                );
                Shopware()->Db()->query(
                    "INSERT INTO `rpay_ratepay_order_shipping` (`s_order_id`) VALUES(?)",
                    array($orderID)
                );
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }
        }

        public function getWhitelistedCSRFActions()
        {
            return [
                'index',
                'saveUserData',
                'calcDesign',
                'calcRequest'
            ];
        }

    }
