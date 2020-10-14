<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\DTO;


class InstallmentRequest
{

    private $totalAmount;
    private $type;
    private $value;
    private $paymentType;

    public function __construct($totalAmount = null, $type = null, $value = null, $paymentType = null)
    {
        $this->totalAmount = $totalAmount;
        $this->type = $type;
        $this->value = $value;
        $this->paymentType = $paymentType;
    }

    /**
     * @return null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param null $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param mixed $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function fromArray($data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
}
