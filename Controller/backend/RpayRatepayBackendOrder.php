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

use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Component\Service\ConfigLoader;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Address;

class Shopware_Controllers_Backend_RpayRatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    private function getSnippet($namespace, $name, $default)
    {
        $ns = Shopware()->Snippets()->getNamespace($namespace);
        return $ns->get($name, $default);
    }

    public function prevalidateAction()
    {
        $params = $this->Request()->getParams();
        $customerId = $params['customerId'];
        //TOOD REMOVE CLASSES BECAUSE PHP 5.4
        $customer = Shopware()->Models()->find(Customer::class, $customerId);

        $billingId = $params['billingId'];
        $billing = Shopware()->Models()->find(Address::class, $billingId);
        $shippingId = $params['shippingId'];
        $shipping = Shopware()->Models()->find(Address::class, $shippingId);

        $paymentTypeName = $params['paymentTypeName'];
        $paymentType = Shopware()->Models()->getRepository(Payment::class)->findOneBy(['name' => $paymentTypeName]);

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
