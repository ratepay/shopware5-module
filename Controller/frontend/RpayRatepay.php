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
use RpayRatePay\Component\Service\ShopwareUtil;
use Shopware\Components\CSRFWhitelistAware;
use RpayRatePay\Component\Service\PaymentProcessor;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\Logger;
use \RpayRatePay\Component\Service\ConfigLoader;
use \Shopware\Plugins\Community\Frontend\RpayRatePay\Services\DfpService;
use Shopware\Plugins\Community\Frontend\RpayRatePay\Services\ProfileConfigService;

class Shopware_Controllers_Frontend_RpayRatepay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * Stores an Instance of the Shopware\Models\Customer\Billing model
     *
     * @var Shopware\Models\Customer\Billing
     */
    private $_config;
    /** @var Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory */
    private $_modelFactory;
    private $_logging;
    private $_customerMessage;

    /** @var ConfigLoader */
    protected $_configLoader;

    /** @var DfpService */
    protected $dfpService;

    /**
     * @return string
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function init()
    {
        $Parameter = $this->Request()->getParams();

        $customerId = null;

        if (isset($Parameter['userid'])) {
            $customerId = $Parameter['userid'];
        } elseif (isset(Shopware()->Session()->sUserId)) {
            $customerId = Shopware()->Session()->sUserId;
        }

        if ($customerId === null) {
            return 'RatePAY frontend controller: No user set';
        }

        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $customerId);

        $netPrices = ShopwareUtil::customerCreatesNetOrders($customer);

        $this->_config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
        $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, false, $netPrices, Shopware()->Shop()->getId());
        $this->_logging = new Shopware_Plugins_Frontend_RpayRatePay_Component_Logging();
        $this->_configLoader = new ConfigLoader(Shopware()->Container()->get('db'));
        $this->dfpService = DfpService::getInstance();
    }

    /**
     *  Checks the Paymentmethod
     */
    public function indexAction()
    {
        if (!$this->isRatePayPayment()) {
            $this->redirect(
                Shopware()->Front()->Router()->assemble(
                    [
                        'controller' => 'checkout',
                        'action' => 'confirm',
                        'forceSecure' => true
                    ]
                )
            );
            return;
        }

        if (!$this->isInstallmentPaymentWithoutCalculation()) {
            Logger::singleton()->info('Proceed with RatePAY payment');
            Shopware()->Session()->RatePAY['errorRatenrechner'] = 'false';
            $this->_proceedPayment();
            return;
        }

        Logger::singleton()->info('RatePAY installment has incomplete calculation');
        Shopware()->Session()->RatePAY['errorRatenrechner'] = 'true';
        $this->redirect(
            Shopware()->Front()->Router()->assemble(
                [
                    'controller' => 'checkout',
                    'action' => 'confirm',
                    'forceSecure' => true
                ]
            )
        );
    }

    public function getQualifiedCustomerDetailsFromParameters()
    {
        $parameters = $this->Request()->getParams();

        $qualifiedParameters = [];

        if (ShopwareUtil::hasValueAndIsNotEmpty('checkoutBillingAddressId', $parameters)) {
            $qualifiedParameters['checkoutBillingAddressId'] = $parameters['checkoutBillingAddressId'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_company', $parameters)) {
            $qualifiedParameters['ratepay_company'] = $parameters['ratepay_company'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_phone', $parameters)) {
            // find out the rules from gateway for validation!
            $qualifiedParameters['ratepay_phone'] = $parameters['ratepay_phone'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_birthyear', $parameters)
            && ShopwareUtil::hasValueAndIsNotEmpty('ratepay_birthmonth', $parameters)
            && ShopwareUtil::hasValueAndIsNotEmpty('ratepay_birthday', $parameters)) {

            $day = $parameters['ratepay_birthday'];
            $month = $parameters['ratepay_birthmonth'];
            $year = $parameters['ratepay_birthyear'];

            if (checkdate($month, $day, $year)) {
                $date = new DateTime();
                $date->setDate(trim($year), trim($month), trim($day));
                $qualifiedParameters['ratepay_dob'] = $date->format('Y-m-d');
            }
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_debit_accountnumber', $parameters)) {
            $qualifiedParameters['ratepay_debit_accountnumber'] = $parameters['ratepay_debit_accountnumber'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_debit_updatedebitdata', $parameters)) {
            $qualifiedParameters['ratepay_debit_updatedebitdata'] = $parameters['ratepay_debit_updatedebitdata'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_debit_bankcode', $parameters)) {
            $qualifiedParameters['ratepay_debit_bankcode'] = $parameters['ratepay_debit_bankcode'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('ratepay_agb', $parameters)) {
            $qualifiedParameters['ratepay_agb'] = $parameters['ratepay_agb'];
        }

        if (ShopwareUtil::hasValueAndIsNotEmpty('userid', $parameters)) {
            $qualifiedParameters['userid'] = $parameters['userid'];
        }

        return $qualifiedParameters;
    }

    /**
     * Updates phone, ustid, company and the birthday for the current user.
     */
    public function saveUserDataAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $Parameter = $this->getQualifiedCustomerDetailsFromParameters();

        $customerModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer');

        /** @var Shopware\Models\Customer\Customer $userModel $userModel */
        $userModel = $customerModel->findOneBy(['id' => Shopware()->Session()->sUserId]);
        $userWrapped = new ShopwareCustomerWrapper($userModel, Shopware()->Models());

        if (isset($Parameter['checkoutBillingAddressId']) && !is_null($Parameter['checkoutBillingAddressId'])) { // From Shopware 5.2 current billing address is sent by parameter
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $customerAddressBilling = $addressModel->findOneBy(['id' => $Parameter['checkoutBillingAddressId']]);
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
        $updateUserData = [];
        $updateAddressData = [];

        if (!is_null($customerAddressBilling)) {
            //shopware before 5.2 ... we could try changing order of if and ifelse
            if (method_exists($customerAddressBilling, 'getBirthday')) {
                $updateAddressData['phone'] = $Parameter['ratepay_phone'] ?: $customerAddressBilling->getPhone();
                if ($customerAddressBilling->getCompany() !== '') {
                    $updateAddressData['company'] = $Parameter['ratepay_company'] ?: $customerAddressBilling->getCompany();
                } else {
                    $updateAddressData['birthday'] = $Parameter['ratepay_dob'] ?: $customerAddressBilling->getBirthday()->format('Y-m-d');
                }

                try {
                    Shopware()->Db()->update('s_user_billingaddress', $updateAddressData, 'userID=' . $Parameter['userid']); // TODO: Parameterize or make otherwise safe
                    Logger::singleton()->info('Customer data was updated');
                } catch (\Exception $exception) {
                    Logger::singleton()->error('RatePAY was unable to update customer data: ' . $exception->getMessage());
                    $return = 'NOK';
                }
            } elseif (method_exists($userModel, 'getBirthday')) { // From Shopware 5.2 birthday is moved to customer object
                $updateAddressData['phone'] = $Parameter['ratepay_phone'] ?: $customerAddressBilling->getPhone();
                if (!is_null($customerAddressBilling->getCompany())) {
                    $updateAddressData['company'] = $Parameter['ratepay_company'] ?: $customerAddressBilling->getCompany();
                } else {
                    $updateUserData['birthday'] = $Parameter['ratepay_dob'] ?: $userModel->getBirthday()->format('Y-m-d');
                }

                try {
                    if (count($updateUserData) > 0) {
                        Shopware()->Db()->update('s_user', $updateUserData, 'id=' . $Parameter['userid']); // ToDo: Why parameter?
                    }
                    if (count($updateAddressData) > 0) {
                        Shopware()->Db()->update('s_user_addresses', $updateAddressData, 'id=' . $Parameter['checkoutBillingAddressId']);
                    }
                    Logger::singleton()->info('Customer data was updated');
                } catch (\Exception $exception) {
                    Logger::singleton()->error('RatePAY was unable to update customer data: ' . $exception->getMessage());
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
            $paymentProcessor = new PaymentProcessor($this->get('db'), new ConfigLoader($this->get('db')));

            Shopware()->Session()->RatePAY['transactionId'] = $resultRequest->getTransactionId();
            $uniqueId = $this->createPaymentUniqueId();
            $orderNumber = $this->saveOrder(Shopware()->Session()->RatePAY['transactionId'], $uniqueId, 17);
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')
                ->findOneBy(['number' => $orderNumber]);

            try {
                if (Shopware()->Session()->sOrderVariables['sBasket']['sShippingcosts'] > 0) {
                    $paymentProcessor->initShipping($order);
                }
                $paymentProcessor->initDiscount($order);
            } catch (\Exception $exception) {
                Logger::singleton()->error($exception->getMessage());
            }

            try {
                $paymentProcessor->setOrderAttributes(
                    $order,
                    $resultRequest,
                    $this->_configLoader->commitShippingAsCartItem(),
                    $this->_configLoader->commitDiscountAsCartItem(),
                    false
                );
            } catch (\Exception $exception) {
                Logger::singleton()->error($exception->getMessage());
            }

            $paymentProcessor->setPaymentStatus($order);

            if (Shopware_Plugins_Frontend_RpayRatePay_Bootstrap::getPCConfig() == true) {
                $paymentProcessor->sendPaymentConfirm($resultRequest->getTransactionId(), $order);
            }

            /**
             * unset DFI token
             */
            $this->dfpService->deleteDfpId();

            /*
             * redirect to success page
             */
            $this->redirect(
                [
                    'controller' => 'checkout',
                    'action' => 'finish',
                    'sUniqueID' => $uniqueId,
                    'forceSecure' => true
                ]
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
        $this->View()->loadTemplate('frontend/payment_rpay_part/RatePAYErrorpage.tpl');
        $customerMessage = $this->_customerMessage;

        if (!empty($customerMessage)) {
            $this->View()->assign('rpCustomerMsg', $customerMessage);
        } else {
            Shopware()->Session()->RatePAY['hidePayment'] = true;
            $this->View()->assign('rpCustomerMsg', Shopware()->Snippets()->getNamespace('RatePAY')->get('UnknownError', 'Unbekannter Fehler'));
        }
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

    /**
     * @return bool
     */
    private function isRatePayPayment()
    {
        return 1 === preg_match('/^rpayratepay(invoice|rate|debit|rate0|prepayment)$/', $this->getPaymentShortName());
    }

    /**
     * @return bool
     */
    private function isInstallmentPaymentWithoutCalculation()
    {
        return in_array($this->getPaymentShortName(), ['rpayratepayrate', 'rpayratepayrate0'])
            && !isset(Shopware()->Session()->RatePAY['ratenrechner']);
    }
}
