<?php


namespace RpayRatePay\Component\InstallmentCalculator\Model;


class OfflineInstallmentCalculatorResult
{

    /**
     * @var InstallmentBuilder
     */
    private $builder;

    /**
     * @var InstallmentCalculatorContext
     */
    private $context;

    /**
     * @var float
     */
    private $monthCount;

    /**
     * @var float
     */
    private $monthlyRate;

    /**
     * @param InstallmentCalculatorContext $context
     * @param InstallmentBuilder $builder
     * @param float $monthCount
     * @param float $monthlyRate
     */
    public function __construct(InstallmentCalculatorContext $context, InstallmentBuilder $builder, $monthCount, $monthlyRate)
    {
        $this->context = $context;
        $this->builder = $builder;
        $this->monthCount = $monthCount;
        $this->monthlyRate = $monthlyRate;
    }

    /**
     * @return InstallmentCalculatorContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return InstallmentBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return float
     */
    public function getMonthCount()
    {
        return $this->monthCount;
    }

    /**
     * @return float
     */
    public function getMonthlyRate()
    {
        return $this->monthlyRate;
    }
}
