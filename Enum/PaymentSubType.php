<?php


namespace RpayRatePay\Enum;


final class PaymentSubType extends Enum
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
}
