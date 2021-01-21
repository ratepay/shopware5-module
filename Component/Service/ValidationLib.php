<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\Service;

use DateTime;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Models\ConfigPayment;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Billing as BillingAddress;
use Shopware\Models\Order\Shipping as ShippingAddress;

class ValidationLib
{

    const VAT_REGEX = [
        "AT" => ['prefix' => ['ATU'], 'regex' => "ATU[0-9]{8}"],
        "BE" => ['prefix' => ['BE'], 'regex' => "BE[0-9]{9}"],
        "BG" => ['prefix' => ['BG'], 'regex' => "BG[0-9]{9,10}"],
        "CH" => ['prefix' => ['CHE', 'CH'], 'regex' => "CHE{0,1}[\.\-\s]{0,1}[0-9]{3}[\.\-\s]{0,1}[0-9]{3}[\.\-\s]{0,1}[0-9]{3}( MWST){0,1}"],
        "CY" => ['prefix' => ['CY'], 'regex' => "CY[0-9]{8}L"],
        "CZ" => ['prefix' => ['CZ'], 'regex' => "CZ[0-9]{8,10}"],
        "DE" => ['prefix' => ['DE'], 'regex' => "DE[0-9]{9}"],
        "DK" => ['prefix' => ['DK'], 'regex' => "DK[0-9]{8}"],
        "EE" => ['prefix' => ['EE'], 'regex' => "EE[0-9]{9}"],
        "EL" => ['prefix' => ['EL', 'GR'], 'regex' => "(EL|GR)[0-9]{9}"],
        "GR" => ['prefix' => ['EL', 'GR'], 'regex' => "(EL|GR)[0-9]{9}"],
        "ES" => ['prefix' => ['ES'], 'regex' => "ES[0-9A-Z][0-9]{7}[0-9A-Z]"],
        "FI" => ['prefix' => ['FI'], 'regex' => "FI[0-9]{8}"],
        "FR" => ['prefix' => ['FR'], 'regex' => "FR[0-9A-Z]{2}[0-9]{9}"],
        "GB" => ['prefix' => ['GB'], 'regex' => "GB([0-9]{9}([0-9]{3})?|[A-Z]{2}[0-9]{3})"],
        "HU" => ['prefix' => ['HU'], 'regex' => "HU[0-9]{8}"],
        "IE" => ['prefix' => ['IE'], 'regex' => "IE[0-9]S[0-9]{5}L"],
        "IT" => ['prefix' => ['IT'], 'regex' => "IT[0-9]{11}"],
        "LT" => ['prefix' => ['LT'], 'regex' => "LT([0-9]{9}|[0-9]{12})"],
        "LU" => ['prefix' => ['LU'], 'regex' => "LU[0-9]{8}"],
        "LV" => ['prefix' => ['LV'], 'regex' => "LV[0-9]{11}"],
        "MT" => ['prefix' => ['MT'], 'regex' => "MT[0-9]{8}"],
        "NL" => ['prefix' => ['NL'], 'regex' => "NL[0-9]{9}B[0-9]{2}"],
        "PL" => ['prefix' => ['PL'], 'regex' => "PL[0-9]{10}"],
        "PT" => ['prefix' => ['PT'], 'regex' => "PT[0-9]{9}"],
        "RO" => ['prefix' => ['RO'], 'regex' => "RO[0-9]{2,10}"],
        "SE" => ['prefix' => ['SE'], 'regex' => "SE[0-9]{12}"],
        "SI" => ['prefix' => ['SI'], 'regex' => "SI[0-9]{8}"],
        "SK" => ['prefix' => ['SK'], 'regex' => "SK[0-9]{10}"],
    ];

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

    public static function isCompanySet(Address $billingAddress)
    {
        return !empty($billingAddress->getCompany());
    }

    public static function isOldEnough($date)
    {
        $today = new DateTime('now');
        if ($date instanceof DateTime === false &&
            preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)
        ) {
            $date = DateTime::createFromFormat('Y-m-d', $date);
        }
        //TODO Age config?
        return $date->diff($today)->y >= 18 && $date->diff($today)->y <= 120;
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
        if ($billing->getId() == $shipping->getId()) {
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
            if ($config->isAllowB2B() === false) {
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

    public static function isCountryValid($allowedCountries, Country $countryBilling)
    {
        $allowedCountries = is_array($allowedCountries) ? $allowedCountries : explode(',', $allowedCountries);
        return array_search($countryBilling->getIso(), $allowedCountries, true) !== false;
    }

    public static function isIbanValid($iban)
    {
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

    public static function isVatIdValid($countryCode, $vatId)
    {
        return isset(self::VAT_REGEX[$countryCode]) ? preg_match('/^' . self::VAT_REGEX[$countryCode]['regex'] . '$/i', $vatId) === 1 : true;
    }
}
