<?php

namespace RpayRatePay\Enum;

use RpayRatePay\PaymentMethods\Debit;
use RpayRatePay\PaymentMethods\Installment;
use RpayRatePay\PaymentMethods\InstallmentZeroPercent;
use RpayRatePay\PaymentMethods\Invoice;
use RpayRatePay\PaymentMethods\PrePayment;
use RuntimeException;
use Shopware\Models\Payment\Payment;

final class PaymentMethods extends Enum
{

    const PAYMENT_INVOICE = 'rpayratepayinvoice';
    const PAYMENT_RATE = 'rpayratepayrate';
    const PAYMENT_DEBIT = 'rpayratepaydebit';
    const PAYMENT_INSTALLMENT0 = 'rpayratepayrate0';
    const PAYMENT_PREPAYMENT = 'rpayratepayprepayment';

    const PAYMENTS = [
        self::PAYMENT_INVOICE => [
            'name' => self::PAYMENT_INVOICE,
            'description' => 'Rechnung',
            'action' => 'rpay_ratepay',
            'active' => 0,
            'position' => 1,
            'additionalDescription' => 'Kauf auf Rechnung',
            'template' => 'ratepay/invoice.tpl',
            'class' => self::PAYMENT_INVOICE,
            'real_class' => Invoice::class,
            'ratepay' => [
                'methodName' => 'INVOICE'
            ]
        ],
        self::PAYMENT_RATE => [
            'name' => self::PAYMENT_RATE,
            'description' => 'Ratenzahlung',
            'action' => 'rpay_ratepay',
            'active' => 0,
            'position' => 2,
            'additionalDescription' => 'Kauf auf Ratenzahlung',
            'template' => 'ratepay/installment.tpl',
            'class' => self::PAYMENT_RATE,
            'real_class' => Installment::class,
            'ratepay' => [
                'methodName' => 'INSTALLMENT'
            ]
        ],
        self::PAYMENT_DEBIT => [
            'name' => self::PAYMENT_DEBIT,
            'description' => 'Lastschrift',
            'action' => 'rpay_ratepay',
            'active' => 0,
            'position' => 3,
            'additionalDescription' => 'Kauf auf SEPA Lastschrift',
            'template' => 'ratepay/debit.tpl',
            'class' => self::PAYMENT_DEBIT,
            'real_class' => Debit::class,
            'ratepay' => [
                'methodName' => 'ELV'
            ]
        ],
        self::PAYMENT_INSTALLMENT0 => [
            'name' => self::PAYMENT_INSTALLMENT0,
            'description' => '0% Finanzierung',
            'action' => 'rpay_ratepay',
            'active' => 0,
            'position' => 4,
            'additionalDescription' => 'Kauf per 0% Finanzierung',
            'template' => 'ratepay/installment.tpl',
            'class' => self::PAYMENT_INSTALLMENT0,
            'real_class' => InstallmentZeroPercent::class,
            'ratepay' => [
                'methodName' => 'INSTALLMENT'
            ]
        ],
        self::PAYMENT_PREPAYMENT => [
            'name' => self::PAYMENT_PREPAYMENT,
            'description' => 'Vorkasse',
            'action' => 'rpay_ratepay',
            'active' => 0,
            'position' => 5,
            'additionalDescription' => 'Kauf per Vorkasse',
            'template' => 'ratepay/prepayment.tpl',
            'class' => self::PAYMENT_PREPAYMENT,
            'real_class' => PrePayment::class,
            'ratepay' => [
                'methodName' => 'PREPAYMENT'
            ]
        ],
    ];

    public static function getNames()
    {
        return array_keys(self::PAYMENTS);
    }

    /**
     * @param string|Payment $paymentMethod
     * @return boolean
     */
    public static function exists($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return $paymentMethod ? array_key_exists($paymentMethod, self::PAYMENTS) : false;
    }

    /**
     * @param string|Payment $paymentMethod
     * @return string
     */
    public static function getRatepayPaymentMethod($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        if (!self::exists($paymentMethod)) {
            throw new RuntimeException('the method ' . $paymentMethod . ' is not a ratepay payment method');
        }
        return self::PAYMENTS[$paymentMethod]['ratepay']['methodName'];
    }

    public static function isInstallment($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return in_array($paymentMethod, [self::PAYMENT_INSTALLMENT0, self::PAYMENT_RATE]);
    }

    public static function isZeroPercentInstallment($paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        return $paymentMethod == self::PAYMENT_INSTALLMENT0;
    }
}
