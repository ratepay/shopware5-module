<?php


namespace RpayRatePay\Component\InstallmentCalculator\Service;


use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentPlanResult;
use RpayRatePay\DTO\InstallmentDetails;
use RpayRatePay\Helper\AbstractSessionHelper;
use RpayRatePay\Models\ProfileConfig;

class SessionHelper extends AbstractSessionHelper
{

    const SESSION_KEY_PLAN_DATA = 'installment_plan_data';

    const SESSION_KEY_PAYMENT_TYPE = 'installment_payment_type';

    const SESSION_KEY_REQUEST_DATA = 'installment_request_data';

    const SESSION_KEY_PROFILE_ID = 'installment_profile_id';

    /**
     * @return InstallmentDetails
     */
    public function getDetails()
    {
        $data = $this->getData(self::SESSION_KEY_PLAN_DATA);

        $object = null;
        if (is_array($data) && isset($data['rate'])) {
            $object = new InstallmentDetails();
            $object->setTotalAmount($data['totalAmount']);
            $object->setAmount($data['amount']);
            $object->setInterestRate($data['interestRate']);
            $object->setInterestAmount($data['interestAmount']);
            $object->setServiceCharge($data['serviceCharge']);
            $object->setAnnualPercentageRate($data['annualPercentageRate']);
            $object->setMonthlyDebitInterest($data['monthlyDebitInterest']);
            $object->setNumberOfRatesFull($data['numberOfRatesFull']);
            $object->setRate($data['rate']);
            $object->setLastRate($data['lastRate']);
            $object->setPaymentType($this->getPaymentType());
        }

        return $object;
    }

    public function setPlanResult(InstallmentPlanResult $planResult)
    {
        $this->setData(self::SESSION_KEY_PLAN_DATA, $planResult->getPlanData());

        $this->setData(self::SESSION_KEY_REQUEST_DATA, [
            'total_amount' => $planResult->getContext()->getTotalAmount(),
            'calculation_type' => $planResult->getContext()->getCalculationType(),
            'calculation_value' => $planResult->getContext()->getCalculationValue()
        ]);

        $this->setData(self::SESSION_KEY_PROFILE_ID, $planResult->getBuilder()->getProfileConfig()->getId());
        $this->setPaymentType($planResult->getDefaultPaymentType());
    }

    /**
     * @return array|null
     */
    public function getPlanData()
    {
        return $this->getData(self::SESSION_KEY_PLAN_DATA);
    }

    /**
     * @param string $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->setData(self::SESSION_KEY_PAYMENT_TYPE, $paymentType);
    }

    /**
     * @return string|null
     */
    public function getPaymentType()
    {
        return $this->getData(self::SESSION_KEY_PAYMENT_TYPE);
    }

    /**
     * @return array{total_amount: float, calculation_type: string, calculation_value:int|float}|null
     */
    public function getRequestData()
    {
        $data = $this->getData(self::SESSION_KEY_REQUEST_DATA);

        return $data ? [
            'total_amount' => $data['total_amount'],
            'calculation_type' => $data['calculation_type'],
            'calculation_value' => $data['calculation_value']
        ] : null;
    }

    /**
     * returns the stored profile which was used to fetch the installment plan
     * @return ProfileConfig|null
     */
    public function getProfile()
    {
        $id = $this->getData(self::SESSION_KEY_PROFILE_ID);

        return $id ? $this->entityManager->find(ProfileConfig::class, $id) : null;
    }

}
