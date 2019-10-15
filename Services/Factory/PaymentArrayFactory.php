<?php


namespace RpayRatePay\Services\Factory;


use Exception;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;

class PaymentArrayFactory
{

    const ARRAY_KEY = 'Payment';
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(SessionHelper $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
    }

    public function getData(PaymentRequestData $paymentRequestData)
    {
        $data = [
            'Method' => strtolower(PaymentMethods::getRatepayPaymentMethod($paymentRequestData->getMethod())),
            'Amount' => $paymentRequestData->getAmount()
        ];

        if (PaymentMethods::isInstallment($paymentRequestData->getMethod())) {
            $installment = $paymentRequestData->getInstallmentDetails();

            if ($installment->getAmount() != $paymentRequestData->getAmount()) {
                throw new Exception(
                    'Attempt to create order with wrong amount in installment calculator.' .
                    'Expected ' . $paymentRequestData->getAmount() . ' Got ' . $installment->getAmount()
                );
            }

            $data = array_merge($data, [
                'DebitPayType' => $installment->getPaymentSubtype(),
                'Amount' => $installment->getTotalAmount(),
                'InstallmentDetails' => [
                    'InstallmentNumber' => $installment->getNumberOfRatesFull(),
                    'InstallmentAmount' => $installment->getRate(),
                    'LastInstallmentAmount' => $installment->getLastRate(),
                    'InterestRate' => $installment->getInterestRate(),
                    'PaymentFirstday' => $installment->getPaymentFirstday()
                ]
            ]);
        }
        return $data;
    }
}
