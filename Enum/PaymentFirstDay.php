<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Enum;


final class PaymentFirstDay extends Enum
{

    const PAY_TYPE_DIRECT_DEBIT = 'DIRECT-DEBIT';
    const PAY_TYPE_BANK_TRANSFER = 'BANK-TRANSFER';
    const PAY_TYPE_FIRSTDAY_SWITCH = 'FIRSTDAY-SWITCH';

    const PAY_TYPES = [
        '2' => self::PAY_TYPE_DIRECT_DEBIT,
        '28' => self::PAY_TYPE_BANK_TRANSFER,
        '2,28' => self::PAY_TYPE_FIRSTDAY_SWITCH
    ];

    public static function getPayTypByFirstPayDay($firstPayDay)
    {
        return self::PAY_TYPES[$firstPayDay];
    }

    public static function getFirstDayForPayType($payType)
    {
        return array_search($payType, self::PAY_TYPES, true);
    }
}
