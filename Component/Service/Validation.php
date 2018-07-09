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
}