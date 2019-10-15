<?php


namespace RpayRatePay\DTO;


use RpayRatePay\Enum\PaymentSubType;

class InstallmentRequest
{

    private $totalAmount;
    private $type;
    private $value;
    private $paymentType;
    private $paymentFirstDay;

    public function __construct($totalAmount = null, $type = null, $value = null, $paymentType = null, $paymentFirstDay = null)
    {
        $this->totalAmount = $totalAmount;
        $this->type = $type;
        $this->value = $value;
        $this->paymentType = $paymentType ?: $paymentFirstDay ? PaymentSubType::getPayTypByFirstPayDay($paymentFirstDay) : null;
        $this->paymentFirstDay = $paymentFirstDay;
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

    /**
     * @return mixed
     */
    public function getPaymentFirstDay()
    {
        return $this->paymentFirstDay;
    }

    /**
     * @param mixed $paymentFirstDay
     */
    public function setPaymentFirstDay($paymentFirstDay)
    {
        $this->paymentFirstDay = $paymentFirstDay;
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
