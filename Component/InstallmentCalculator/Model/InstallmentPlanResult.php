<?php


namespace RpayRatePay\Component\InstallmentCalculator\Model;


use RpayRatePay\Enum\PaymentFirstDay;

class InstallmentPlanResult
{

    /**
     * @var InstallmentBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $planData;

    /**
     * @var InstallmentCalculatorContext
     */
    private $context;

    public function __construct(InstallmentCalculatorContext $context, InstallmentBuilder $builder, $planData)
    {
        $this->context = $context;
        $this->builder = $builder;
        $this->planData = $planData;
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
     * @return array
     */
    public function getPlanData()
    {
        return $this->planData;
    }

    /**
     * @return string
     */
    public function getDefaultPaymentType()
    {
        return PaymentFirstDay::getPayTypByFirstPayDay($this->planData['paymentFirstday']);
    }

    /**
     * @return array<string>
     */
    public function getAllowedPaymentTypes()
    {
        return $this->builder->getInstallmentPaymentConfig()->getAllowedPaymentTypes();
    }

    /**
     * @param string $paymentType
     * @return bool
     */
    public function isPaymentTypeAllowed($paymentType)
    {
        return in_array($paymentType, $this->getAllowedPaymentTypes(), true);
    }

}
