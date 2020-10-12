<?php

namespace RpayRatePay\Component\Service;
use Shopware;

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 */
class ShopwareUtil
{
    protected $debitPayTypes = [
        '2' => 'DIRECT-DEBIT',
        '28' => 'BANK-TRANSFER',
        '2,28' => 'FIRSTDAY-SWITCH'
    ];

    const METHODS = [
        'INVOICE' => 'rpayratepayinvoice',
        'INSTALLMENT' => 'rpayratepayrate',
        'ELV' => 'rpayratepaydebit',
        'INSTALLMENT0' => 'rpayratepayrate0',
        'PREPAYMENT' => 'rpayratepayprepayment'
    ];

    /**
     * Returns the methodname for RatePAY
     *
     * @param $shopwareMethod string the shopware method name
     * @return string
     */
    public static function getPaymentMethod($shopwareMethod)
    {
        return array_flip(self::METHODS)[$shopwareMethod];
    }

    /**
     * returns the method name for shopware
     * @param $ratepayMethod string the ratepay method name
     * @return mixed
     */
    public static function getShopwarePaymentMethod($ratepayMethod)
    {
        return self::METHODS[$ratepayMethod];
    }

    /**
     * Return the debit pay type depending on payment first day
     *
     * @param $paymentFirstday
     * @return String
     */
    public function getDebitPayType($paymentFirstday)
    {
        return $this->debitPayTypes[$paymentFirstday];
    }

    /**
     * @param $table string
     * @param $column string
     *
     * @return bool
     */
    public static function tableHasColumn($table, $column)
    {
        $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
        $res = Shopware()->Db()->fetchRow($sql);
        if (empty($res)) {
            return false;
        }
        return true;
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     * @return bool
     */
    public static function customerCreatesNetOrders(Shopware\Models\Customer\Customer $customer)
    {
        return $customer->getGroup()->getTax() === false;
    }

    /**
     * @param \Shopware\Models\Payment\Payment $payment
     * @return int
     */
    public static function getStatusAfterRatePayPayment($payment)
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        switch ($payment->getName()) {
            case 'rpayratepayinvoice':
                return (int)$config->get('RatePayInvoicePaymentStatus');
            case 'rpayratepayrate':
                return (int)$config->get('RatePayInstallmentPaymentStatus');
            case 'rpayratepaydebit':
                return (int)$config->get('RatePayDebitPaymentStatus');
            case 'rpayratepayrate0':
                return (int)$config->get('RatePayInstallment0PaymentStatus');
            case 'rpayratepayprepayment':
                return (int)$config->get('RatePayPrepaidPaymentStatus');
            default:
                Logger::singleton()->error(
                    'Unable to define status for unknown method: ' . $payment->getName()
                );
                return 17;
        }

    }

    /**
     * @param $key
     * @param $array
     * @return bool
     */
    public static function hasValueAndIsNotEmpty($key, $array)
    {
        return key_exists($key, $array) && !empty($array[$key]);
    }

    /**
     * @param $version
     * @return bool
     */
    public static function assertMinimumShopwareVersion($version)
    {
        $sExpected = explode('.', $version);
        $expected = array_map('intval', $sExpected);
        $sConfigured = explode('.', Shopware()->Config()->version);
        $configured = array_map('intval', $sConfigured);

        for ($i = 0; $i < 3; $i++) {
            if ($expected[$i] < $configured[$i]) {
                return true;
            }

            if ($expected[$i] > $configured[$i]) {
                return false;
            }
        }

        return true;
    }
}
