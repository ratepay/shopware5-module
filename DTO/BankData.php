<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\DTO;

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
     * BankData constructor.
     * @param $accountHolder
     * @param $iban
     * @param $bankCode
     * @param $accountNumber
     */
    public function __construct($accountHolder, $iban = null, $bankCode = null, $accountNumber = null)
    {
        $this->accountHolder = $accountHolder;
        $this->iban = $iban;
        $this->bankCode = $bankCode;
        $this->accountNumber = $accountNumber;
    }

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
}
