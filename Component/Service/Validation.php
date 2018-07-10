<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 09.07.18
 * Time: 16:03
 */
namespace RpayRatePay\Component\Service;

use Shopware\Models\Customer\Customer;

class Validation
{
    /**
     * @param Customer $customer
     * @return bool
     */
    public static function isBirthdayValid(Customer $customer)
    {
        $birthday = $customer->getBirthday();

        //throws exception, method not found
        //if necessary, we could check for it through reflection
        //TODO, ask Anni about this
        /*if (empty($birthday) || is_null($birthday)) {
            $birthday = $customer->getBilling()->getBirthday();
        }*/

        $return = false;
        if (!is_null($birthday)) {
            if (!$birthday instanceof \DateTime) {
                $birthday = new \DateTime($birthday);
            }

            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday->format('Y-m-d')) !== 0)
            {
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
        $phone = $customer->getBilling()->getPhone();

        return !empty($phone);
    }

    /**
     * Compares billing and shipping addresses.
     * If shipping is null, returns true.
     *
     * @param Shopware\Models\Customer\Address $billing
     * @param Shopware\Models\Customer\Address $shipping
     */
    public static function areBillingAndShippingSame($billing, $shipping = null)
    {
        $classFunctions = array(
            'getCompany',
            'getFirstname',
            'getLastName',
            'getStreet',
            'getZipCode',
            'getCity',
        );

        if (!is_null($shipping)) {
            foreach ($classFunctions as $function) {
                if (strval(call_user_func(array($billing, $function))) != strval(call_user_func(array($shipping, $function)))) {
                    return false;
                }
            }
        }

        return true;
    }
}