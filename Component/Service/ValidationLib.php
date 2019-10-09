<?php

namespace RpayRatePay\Component\Service;

use DateTime;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Models\ConfigPayment;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Billing as BillingAddress;
use Shopware\Models\Order\Shipping as ShippingAddress;

class ValidationLib
{
    /**
     * @param Customer $customer
     * @param Address $billingAddress
     * @return bool
     */
    public static function isBirthdayValid(Customer $customer, Address $billingAddress)
    {
        if (self::isCompanySet($billingAddress) === true) {
            return true;
        }

        /** @var DateTime $birthday */
        $birthday = $customer->getBirthday();

        if (!is_null($birthday)) {
            if (!is_null($birthday)) {
                return self::isOldEnough($birthday);
            }
        }
        return false;
    }

    public static function isOldEnough($date)
    {
        $today = new DateTime('now');
        if ($date instanceof \DateTime === false &&
            preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)
        ) {
            $date = \DateTime::createFromFormat('Y-m-d', $date);
        }
        //TODO Age config?
        return $date->diff($today)->y >= 18 && $date->diff($today)->y <= 120;
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
     * @param Address|BillingAddress $billing
     * @param Address|ShippingAddress $shipping
     * @return bool
     */
    public static function areBillingAndShippingSame($billing, $shipping = null)
    {
        if($billing->getId() == $shipping->getId()) {
            return true;
        }
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
     * @param ConfigPayment $config
     * @param float $totalAmount
     * @return bool
     */
    public static function areAmountsValid($b2b, ConfigPayment $config, $totalAmount)
    {
        if ($totalAmount < $config->getLimitMin()) {
            return false;
        }

        if ($b2b) {
            if ($config->getB2b() != 1) {
                return false;
            }

            $b2bMax = $config->getLimitMaxB2b() > 0 ? $config->getLimitMaxB2b() : $config->getLimitMax();

            if ($totalAmount > $b2bMax) {
                return false;
            }
        } else {
            if ($totalAmount > $config->getLimitMax()) {
                return false;
            }
        }

        return true;
    }

    public static function isCurrencyValid($allowedCurrencies, $currency)
    {
        $allowedCurrencies = is_array($allowedCurrencies) ? $allowedCurrencies : explode(',', $allowedCurrencies);
        return array_search($currency, $allowedCurrencies, true) !== false;
    }

    public static function isCountryValid($allowedCountries, \Shopware\Models\Country\Country $countryBilling)
    {
        $allowedCountries = is_array($allowedCountries) ? $allowedCountries : explode(',', $allowedCountries);
        return array_search($countryBilling->getIso(), $allowedCountries, true) !== false;
    }

    public static function isCompanySet(Address $billingAddress)
    {
        return !empty($billingAddress->getCompany());
    }
}
