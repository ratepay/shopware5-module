<?php declare(strict_types=1);
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Util;


use RpayRatePay\DTO\BankData;
use Shopware\Models\Customer\Address;

class BankDataUtil
{

    public static function getAvailableAccountHolder(Address $address, BankData $bankData = null)
    {
        $accountHolders = [
            static::getDefaultAccountHolder($address)
        ];
        if ($address->getCompany()) {
            $accountHolders[] = $address->getCompany();
        }
        $currentAccountHolder = $bankData && !empty($bankData->getAccountHolder()) ? $bankData->getAccountHolder() : null;
        if ($currentAccountHolder && !in_array($currentAccountHolder, $accountHolders)) {
            $accountHolders[] = $currentAccountHolder;
        }
        return $accountHolders;
    }

    public static function getDefaultAccountHolder(Address $address)
    {
        return $address->getFirstname() . ' ' . $address->getLastname();
    }

}
