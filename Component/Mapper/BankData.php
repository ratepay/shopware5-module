<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 17.07.18
 * Time: 09:48
 */

namespace RpayRatePay\Component\Mapper;


class BankData
{
    /**
     * @var string
     */
    private $accountHolder;

    /**
     * @var string|null
     */
    private $iban;

    /**
     * @var string|null
     */
    private $bankCode;

    /**
     * @var string|null
     */
    private $accountNumber;

    /**
     * @return string
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * @return null|string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @return null|string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @return null|string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * BankData constructor.
     * @param $accountHolder
     * @param $iban
     * @param $bankCode
     * @param $accountNumber
     */
    private function __construct($accountHolder, $iban = null, $bankCode = null, $accountNumber = null)
    {
        $this->accountHolder = $accountHolder;
        $this->iban = $iban;
        $this->bankCode = $bankCode;
        $this->accountNumber = $accountNumber;
    }

    /**
     * @param $accountHolder
     * @param $bankCode
     * @param $accountNumber
     * @return BankData
     */
    public static function instantiateOldSystem($accountHolder, $bankCode, $accountNumber)
    {
        return new BankData($accountHolder, null, $bankCode, $accountNumber);
    }

    /**
     * @param $accountHolder
     * @param $iban
     * @return BankData
     */
    public static function instantiateNewSystem($accountHolder, $iban)
    {
        return new BankData($accountHolder,  $iban);
    }

    /**
     * @return BankData
     */
    public static function instantiateFromSession()
    {
        $sessionArray = Shopware()->Session()->RatePAY['bankdata'];

        $bankCode = $sessionArray['bankcode'];
        $accountHolder = $sessionArray['bankholder'];

        if (!empty($bankCode)) {
            return self::instantiateOldSystem($accountHolder, $bankCode, $sessionArray['account']);
        } else {
            return self::instantiateNewSystem($accountHolder, $sessionArray['account']);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $a = [
            'Owner' => $this->getAccountHolder()
        ];

        if ($this->getBankCode() !== null) {
            $a['BankAccountNumber'] = $this->getAccountNumber();
            $a['BankCode'] = $this->getBankCode();
        } else {
            $a['Iban'] = $this->getIban();
        }

        return $a;
    }
}