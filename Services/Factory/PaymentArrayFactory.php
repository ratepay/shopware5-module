<?php


namespace RpayRatePay\Services\Factory;


use Exception;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\ShopwareUtil;
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
            //TODO refactor
            $serviceUtil = new ShopwareUtil();

            $data['Payment']['DebitPayType'] = $serviceUtil->getDebitPayType(
                $this->getSession()->RatePAY['ratenrechner']['payment_firstday']
            );

            if ($data['Payment']['DebitPayType'] == 'DIRECT-DEBIT') {
                $elv = true;
            }

            $calculatorAmountWithoutInterest = $this->getSession()->RatePAY['ratenrechner']['amount'];

            if ((string)$calculatorAmountWithoutInterest !== (string)$paymentRequestData->getAmount()) {
                throw new Exception(
                    'Attempt to create order with wrong amount in installment calculator.' .
                    'Expected ' . $paymentRequestData->getAmount() . ' Got ' . $calculatorAmountWithoutInterest
                );
            }

            $data['Payment']['Amount'] = $this->getSession()->RatePAY['ratenrechner']['total_amount'];
            $data['Payment']['InstallmentDetails'] = [
                'InstallmentNumber' => $this->getSession()->RatePAY['ratenrechner']['number_of_rates'],
                'InstallmentAmount' => $this->getSession()->RatePAY['ratenrechner']['rate'],
                'LastInstallmentAmount' => $this->getSession()->RatePAY['ratenrechner']['last_rate'],
                'InterestRate' => $this->getSession()->RatePAY['ratenrechner']['interest_rate'],
                'PaymentFirstday' => $this->getSession()->RatePAY['ratenrechner']['payment_firstday']
            ];

        }
        return $data;
    }
}
