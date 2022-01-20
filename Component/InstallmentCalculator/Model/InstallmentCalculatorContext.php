<?php


namespace RpayRatePay\Component\InstallmentCalculator\Model;

use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\DTO\PaymentConfigSearch;

class InstallmentCalculatorContext
{

    /**
     * @var string|null
     */
    private $calculationType;

    /**
     * @var float|int|null
     */
    private $calculationValue;

    /**
     * @var float|null
     */
    private $totalAmount;

    /**
     * @var \RpayRatePay\DTO\PaymentConfigSearch
     */
    private $paymentConfigSearch;

    /**
     * @param PaymentConfigSearch $paymentConfigSearch
     * @param float $totalAmount
     * @param string|null $calculationType
     * @param float|null $calculationValue
     */
    public function __construct(PaymentConfigSearch $paymentConfigSearch, $totalAmount, $calculationType = null, $calculationValue = null)
    {
        $paymentConfigSearch->setTotalAmount($totalAmount);

        $this->paymentConfigSearch = $paymentConfigSearch;
        $this->totalAmount = $totalAmount;
        $this->calculationType = $calculationType;
        if ($this->getCalculationType() === InstallmentService::CALCULATION_TYPE_TIME) {
            $this->calculationValue = (int)$calculationValue;
        } else {
            $this->calculationValue = (float)$calculationValue;
        }
    }

    /**
     * @return string
     */
    public function getCalculationType()
    {
        return $this->calculationType;
    }

    /**
     * @return float|int|null
     */
    public function getCalculationValue()
    {
        return $this->calculationValue;
    }

    /**
     * @return ?float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @return PaymentConfigSearch
     */
    public function getPaymentConfigSearch()
    {
        return $this->paymentConfigSearch;
    }
}
