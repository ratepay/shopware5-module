<?php

namespace RpayRatePay\Component\Service;

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use Shopware\Models\Customer\Billing;
use Shopware\Models\Customer\Customer;

class ValidationLib
{
    /**
     * @param Customer $customer
     * @return bool
     */
    public static function isBirthdayValid(Customer $customer, $b2b = false)
    {
        if ($b2b) {
            return true;
        }

        $birthday = $customer->getBirthday();

        if (empty($birthday) || is_null($birthday)) {
            $customerWrapped = new ShopwareCustomerWrapper($customer, Shopware()->Models());
            $birthday = $customerWrapped->getBilling('birthday');
        }

        $return = false;
        if (!is_null($birthday)) {
            if (!$birthday instanceof \DateTime) {
                $birthday = new \DateTime($birthday);
            }

            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday->format('Y-m-d')) !== 0) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * @param Customer $customer
     * @return bool
     */
    public static function isTelephoneNumberSet(Customer $customer)
    {
        $customerWrapped = new ShopwareCustomerWrapper($customer, Shopware()->Models());
        $phone = $customerWrapped->getBilling('phone');

        return !empty($phone);
    }

    /**
     * Compares billing and shipping addresses.
     * If shipping is null, returns true.
     *
     * @param Shopware\Models\Customer\Address|Shopware\Models\Customer\Billing $billing
     * @param Shopware\Models\Customer\Address|Shopware\Models\Customer\Shipping $shipping
     */
    public static function areBillingAndShippingSame($billing, $shipping = null)
    {
        $classFunctions = [
            'getCompany',
            'getFirstname',
            'getLastName',
            'getStreet',
            'getZipCode',
            'getCity',
        ];

        if (!is_null($shipping)) {
            foreach ($classFunctions as $function) {
                if (strval(call_user_func([$billing, $function])) != strval(call_user_func([$shipping, $function]))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param bool $b2b
     * @param array $configData
     * @param float $totalAmount
     * @return bool
     */
    public static function areAmountsValid($b2b, $configData, $totalAmount)
    {
        if ($totalAmount < $configData['limit_min']) {
            return false;
        }

        if ($b2b) {
            if ($configData['b2b'] != '1') { //this is a string, for some reason
                return false;
            }

            $b2bmax = $configData['limit_max_b2b'] > 0 ? $configData['limit_max_b2b'] : $configData['limit_max'];

            if ($totalAmount > $b2bmax) {
                return false;
            }
        } else {
            if ($totalAmount > $configData['limit_max']) {
                return false;
            }
        }

        return true;
    }
}
