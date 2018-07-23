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
 * RpayRatepayBackendOrder
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */

use RpayRatePay\Component\Mapper\BankData;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Component\Service\ConfigLoader;
use Shopware\Components\DependencyInjection\Bridge\Config;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Address;

class Shopware_Controllers_Backend_RpayRatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @param string $namespace
     * @param string $name
     * @param string $default
     * @return mixed
     */
    private function getSnippet($namespace, $name, $default)
    {
        $ns = Shopware()->Snippets()->getNamespace($namespace);
        return $ns->get($name, $default);
    }

    /**
     * Write to session. We must because there is no way to send extra data with order create request.
     */
    public function setExtendedDataAction()
    {
        Shopware()->Pluginlogger()->info('Now calling setExtendedData');
        $params = $this->Request()->getParams();
        $iban = trim($params["iban"]);
        $accountNumber = trim($params["accountNumber"]);
        $bankCode = trim($params["bankCode"]);

        $sessionLoader = new SessionLoader(Shopware()->BackendSession());

        $sessionLoader->setBankData(
            $accountNumber ? $accountNumber : $iban,
            $bankCode, Shopware()->BackendSession()
        );

        $this->view->assign([
            'success' => true,
        ]);
    }

    private function getInstallmentBuilder($isSandbox, $profileId, $securityCode)
    {
        $installmentBuilder = new RatePAY\Frontend\InstallmentBuilder($isSandbox);
        $installmentBuilder->setProfileId($profileId);
        $installmentBuilder->setSecurityCode($securityCode);
        return $installmentBuilder;
    }

    /**
     * @param $paymentMeansName
     * @param $addressObj
     * @param $shopId
     * @return \RatePAY\Frontend\InstallmentBuilder
     */
    private function getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId)
    {
        $config = $this->getConfig($paymentMeansName, $addressObj, $shopId);

        //TODO handle if not exists
        $isSandbox = ((int)($config['sandbox']) === 1);
        $profileId = $config["profileId"];
        $countryIso = PaymentRequestData::findCountryISO($addressObj);
        $securityCodeKey = ConfigLoader::getSecurityCodeKey($countryIso, true);
        $securityCode = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get($securityCodeKey);

        $installmentBuilder = $this->getInstallmentBuilder($isSandbox, $profileId, $securityCode);
        return $installmentBuilder;
    }

    /**
     * @param $paymentMeansName
     * @param $addressObj
     * @param $shopId
     * @return array
     */
    private function getConfig($paymentMeansName, $addressObj, $shopId)
    {
        $configLoader = new ConfigLoader(Shopware()->Db());
        $paymentColumn = $configLoader->getPaymentColumnFromPaymentMeansName($paymentMeansName);

        $countryIso = PaymentRequestData::findCountryISO($addressObj);

        $config = $configLoader->getPluginConfigForPaymentType(
            $shopId,
            $countryIso,
            $paymentColumn,
            true
        );
        return $config;
    }

    public function getInstallmentInfoAction()
    {
        //TODO add try/catch block
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];
        $paymentMeansName = $params['paymentTypeName'];
        $totalAmount = $params['totalAmount'];

        $addressObj = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        $installmentBuilder = $this->getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId);
        $result = $installmentBuilder->getInstallmentCalculatorAsJson($totalAmount);

        $this->view->assign([
            'success' => true,
            'termInfo' => json_decode($result, true) //to prevent double encode
        ]);
    }

    public function getInstallmentPlanAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];
        $paymentMeansName = $params['paymentMeansName'];

        $type = $params['type'];
        $val = $params['value'];
        $totalAmount = $params['totalAmount'];

        $addressObj = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        $installmentBuilder = $this->getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId);

        try {
            //Shopware()->Pluginlogger()->info("Get installment plan with total $totalAmount type $type val $val.");
            $result = $installmentBuilder->getInstallmentPlanAsJson($totalAmount, $type, $val);
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
            return;
        }
        $this->view->assign([
            'success' => true,
            'plan' => json_decode($result, true) //to prevent double encode
        ]);
    }


    public function prevalidateAction()
    {
        $params = $this->Request()->getParams();
        $customerId = $params['customerId'];
        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $customerId);

        $billingId = $params['billingId'];
        $billing = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        $shippingId = $params['shippingId'];
        $shipping = Shopware()->Models()->find('Shopware\Models\Customer\Address', $shippingId);

        $paymentTypeName = $params['paymentTypeName'];
        $paymentType = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(['name' => $paymentTypeName]);

        $totalCost = $params['totalCost'];

        $validator = new Validation($customer, $paymentType);
        $shop = Shopware()->Shop();
        $shopId = $shop->getId();

        $configLoader = new ConfigLoader();
        $paymentTypeColumn = $configLoader->getPaymentColumnFromPaymentMeansName($paymentTypeName);
        $configData = $configLoader->getPluginConfigForPaymentType($shopId, $countryIso);
        $country = $billing->getCountry();

        $validations = $this->validateCustomer($customer);

        if (count($validations) == 0) {
            $this->view->assign([
                'success' => true,
            ]);
        } else {
            $this->view->assign([
                'success' => false,
                'messages' => $validations
            ]);
        }
    }

    private function validateCustomer($customer, $validator)
    {
        $validations = [];
        if (!ValidationLib::isBirthdayValid($customer)) {
            $validations[] = $this->getSnippet("RatePAY/backend/backend_orders","birthday_not_valid", "Geburtstag nicht gültig.");
        }

        if (!ValidationLib::isTelephoneNumberSet($customer)) {
            $validations[] = $this->getSnippet("RatePAY/backend/backend_orders","telephone_not_set",  "Kunden-Telefonnummer nicht gesetzt.");
        }


        return $validations;

    }

    private function validateCart()
    {

    }

}
