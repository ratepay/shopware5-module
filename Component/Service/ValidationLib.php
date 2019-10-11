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

    public static function isIbanValid($iban)
    {
        return preg_match_all('/^[A-Za-z]{2}\d{1,32}$/', $iban) === 1;
        //TODO is this valid?
        //got this from https://stackoverflow.com/questions/20983339/validate-iban-php
        $iban = strtolower(str_replace(' ', '', $iban));
        $Countries = array('al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24);
        $Chars = array('a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35);

        if (strlen($iban) == $Countries[substr($iban, 0, 2)]) {

            $MovedChar = substr($iban, 4) . substr($iban, 0, 4);
            $MovedCharArray = str_split($MovedChar);
            $NewString = "";

            foreach ($MovedCharArray AS $key => $value) {
                if (!is_numeric($MovedCharArray[$key])) {
                    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                }
                $NewString .= $MovedCharArray[$key];
            }

            if (bcmod($NewString, '97') == 1) {
                return true;
            }
        }
        return false;
    }
}
