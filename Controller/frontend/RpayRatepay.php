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

use RpayRatePay\Component\Service\SessionLoader;
use Shopware\Components\CSRFWhitelistAware;
use RpayRatePay\Component\Service\PaymentProcessor;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\Logger;

class Shopware_Controllers_Frontend_RpayRatepay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * Stores an Instance of the Shopware\Models\Customer\Billing model
     *
     * @var Shopware\Models\Customer\Billing
     */
    private $_config;
    private $_modelFactory;
    private $_logging;
    private $_customerMessage;

    /**
     * Initiates the Object
     */
    public function init()
    {
        $Parameter = $this->Request()->getParams();

        $customerId = null;

        if (isset($Parameter['userid'])) {
            $customerId = $Parameter['userid'];
        } else if (isset(Shopware()->Session()->sUserId)) {
            $customerId = Shopware()->Session()->sUserId;
        }

        if ($customerId === null) {
            return "RatePAY frontend controller: No user set";
        }

        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $customerId);

        $netPrices = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::customerCreatesNetOrders($customer);

        $this->_config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
        $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, false, $netPrices);
        $this->_logging      = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();
    }

    /**
     *  Checks the Paymentmethod
     */
    public function indexAction()
    {
        Shopware()->Session()->RatePAY['errorRatenrechner'] = 'false';
        if (preg_match("/^rpayratepay(invoice|rate|debit|rate0)$/", $this->getPaymentShortName())) {
            if ($this->getPaymentShortName() === 'rpayratepayrate' && !isset(Shopware()->Session()->RatePAY['ratenrechner'])
            ) {
                Shopware()->Session()->RatePAY['errorRatenrechner'] = 'true';
                $this->redirect(
                    Shopware()->Front()->Router()->assemble(
                        array(
                            'controller'  => 'checkout',
                            'action'      => 'confirm',
                            'forceSecure' => true
                        )
                    )
                );
            } elseif ($this->getPaymentShortName() === 'rpayratepayrate0' && !isset(Shopware()->Session()->RatePAY['ratenrechner'])) {
                Shopware()->Session()->RatePAY['errorRatenrechner'] = 'true';
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
                Logger::singleton()->info('proceed');
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

        /** @var Shopware\Models\Customer\Customer $userModel */
        $userModel = $customerModel->findOneBy(array('id' => Shopware()->Session()->sUserId));
        $userWrapped = new ShopwareCustomerWrapper($userModel);

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
            $customerAddressBilling = $userWrapped->getBilling();
        }

        $return = 'OK';
        $updateUserData = array();
        $updateAddressData = array();

        if (!is_null($customerAddressBilling)) {
            if (method_exists($customerAddressBilling, 'getBirthday')) {
                $updateAddressData['phone'] = $Parameter['ratepay_phone'] ? : $customerAddressBilling->getPhone();
                if ($customerAddressBilling->getCompany() !== "") {
                    $updateAddressData['company'] = $Parameter['ratepay_company'] ? : $customerAddressBilling->getCompany();
                } else {
                    $updateAddressData['birthday'] = $Parameter['ratepay_dob'] ? : $customerAddressBilling->getBirthday()->format("Y-m-d");
                }

                try {
                    Shopware()->Db()->update('s_user_billingaddress', $updateAddressData, 'userID=' . $Parameter['userid']); // ToDo: Why parameter?
                    Logger::singleton()->info('Kundendaten aktualisiert.');
                } catch (Exception $exception) {
                    Logger::singleton()->error('Fehler beim Updaten der Userdaten: ' . $exception->getMessage());
                    $return = 'NOK';
                }

            } elseif (method_exists($userModel, 'getBirthday')) { // From Shopware 5.2 birthday is moved to customer object
                $updateAddressData['phone'] = $Parameter['ratepay_phone'] ? : $customerAddressBilling->getPhone();
                if (!is_null($customerAddressBilling->getCompany())) {
                    $updateAddressData['company'] = $Parameter['ratepay_company'] ? : $customerAddressBilling->getCompany();
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
                    Logger::singleton()->info('Kundendaten aktualisiert.');
                } catch (Exception $exception) {
                    Logger::singleton()->error('Fehler beim Updaten der User oder Address daten: ' . $exception->getMessage());
                    $return = 'NOK';
                }
            } else {
                $return = 'NOK';
            }
        }

        $sessionLoader = new SessionLoader(Shopware()->Session());
        if ($Parameter['ratepay_debit_updatedebitdata']) {
            $sessionLoader->setBankData(
                $userModel->getId(),
    //            $customerAddressBilling->getFirstname() . " " . $customerAddressBilling->getLastname(),
                $Parameter['ratepay_debit_accountnumber'],
                $Parameter['ratepay_debit_bankcode']
            );
        }

        echo $return;
    }

    /**
     * Procceds the whole Paymentprocess
     */
    private function _proceedPayment()
    {

        $resultRequest = $this->_modelFactory->callPaymentRequest();

        if ($resultRequest->isSuccessful()) {
            $paymentProcessor = new PaymentProcessor(Shopware()->Db());

            Shopware()->Session()->RatePAY['transactionId'] = $resultRequest->getTransactionId();
            $uniqueId = $this->createPaymentUniqueId();
            $orderNumber = $this->saveOrder(Shopware()->Session()->RatePAY['transactionId'], $uniqueId, 17);
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(['number' => $orderNumber]);

            try {
                if (Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'] > 0) {
                    $paymentProcessor->initShipping($order);
                }

            } catch (Exception $exception) {
                Logger::singleton()->error($exception->getMessage());
            }

            try {
                $paymentProcessor->setOrderAttributes($order,
                    $resultRequest,
                    Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayUseFallbackShippingItem')
                );
            } catch (Exception $exception) {
                Logger::singleton()->error($exception->getMessage());
            }

            $paymentProcessor->setPaymentStatusPaid($order);

            if (Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPCConfig() == true) {
                $paymentProcessor->sendPaymentConfirm($resultRequest->getTransactionId(), $order);
            }

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
            $this->_customerMessage = $resultRequest->getCustomerMessage();
            $this->_error();
        }

        // Clear RatePAY session after call for authorization
        Shopware()->Session()->RatePAY = [];
    }

    /**
     * Redirects the User in case of an error
     */
    private function _error()
    {
        $this->View()->loadTemplate("frontend/payment_rpay_part/RatePAYErrorpage.tpl");
        $customerMessage = $this->_customerMessage;

        if (!empty($customerMessage)) {
            $this->View()->assign('rpCustomerMsg', $customerMessage);
        } else {
            Shopware()->Session()->RatePAY['hidePayment'] = true;

            $shopId = Shopware()->Shop()->getId();
            $customerModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer');
            $userModel = $customerModel->findOneBy(array('id' => Shopware()->Session()->sUserId));
            $userModelWrapped = new ShopwareCustomerWrapper($userModel, Shopware()->Models());
            $countryBilling = $userModelWrapped->getBillingCountry();
            $config = $this->getRatePayPluginConfigByCountry($shopId, $countryBilling);

            $this->View()->assign('rpCustomerMsg', $config['error-default']);
        }
    }

    /**
     * Get ratepay plugin config from rpay_ratepay_config table
     *
     * @param $shopId
     * @param $country
     * @return array
     */
    private function getRatePayPluginConfigByCountry($shopId, $country, $backend=false) {
        //fetch correct config for current shop based on user country
        $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $country->getIso());

        //get ratepay config based on shopId and profileId
        return Shopware()->Db()->fetchRow('
            SELECT
            *
            FROM
            `rpay_ratepay_config`
            WHERE
            `shopId` =?
            AND
            `profileId`=?
            AND 
            backend=?
        ', array($shopId, $profileId, $backend));
    }

    /**
     * calcDesign-function for installment
     */
    public function calcDesignAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $calcPath = realpath(dirname(__FILE__) . '/../../Views/responsive/frontend/installment/php/');
        require_once $calcPath . '/PiRatepayRateCalc.php';
        require_once $calcPath . '/path.php';
        require_once $calcPath . '/PiRatepayRateCalcDesign.php';
    }

    /**
     * calcRequest-function for installment
     */
    public function calcRequestAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $calcPath = realpath(dirname(__FILE__) . '/../../Views/responsive/frontend/installment/php/');
        require_once $calcPath . '/PiRatepayRateCalc.php';
        require_once $calcPath . '/path.php';
        require_once $calcPath . '/PiRatepayRateCalcRequest.php';
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
