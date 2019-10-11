<?php


namespace RpayRatePay\PaymentMethods;


use RpayRatePay\Enum\PaymentSubType;
use RpayRatePay\Services\InstallmentService;

class Installment extends Debit
{

    /**
     * @var object|InstallmentService
     */
    private $installmentService;

    public function __construct()
    {
        parent::__construct();
        $this->installmentService = $this->container->get(InstallmentService::class);
    }

    public function getCurrentPaymentDataAsArray($userId)
    {
        $installmentData = $this->sessionHelper->getData('installment_calculator_input', []);
        $this->isBankDataRequired = PaymentSubType::getPayTypByFirstPayDay($installmentData['payment_firstday']) === PaymentSubType::PAY_TYPE_DIRECT_DEBIT;
        $data = parent::getCurrentPaymentDataAsArray($userId);
        $data['ratepay']['installment'] = $installmentData;
        return $data;
    }

    public function validate($paymentData)
    {
        $return = parent::validate($paymentData);

        $installmentData = isset($paymentData['ratepay']['installment']) ? $paymentData['ratepay']['installment'] : [];

        $this->isBankDataRequired = PaymentSubType::getPayTypByFirstPayDay($installmentData['payment_firstday']) === PaymentSubType::PAY_TYPE_DIRECT_DEBIT;

        if($installmentData == null ||
            !isset(
                $installmentData['type'],
                $installmentData['value'],
                $installmentData['payment_type'],
                $installmentData['payment_firstday']
            ) ||
            empty($installmentData['type']) ||
            empty($installmentData['value']) ||
            empty($installmentData['payment_type']) ||
            empty($installmentData['payment_firstday'])
        ) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidCalculator');
        }

        if($this->isBankDataRequired) {
            if(!isset($installmentData['sepa_agreement']) || empty($installmentData['sepa_agreement'])) {
                $return['sErrorMessages'][] = $this->getTranslatedMessage('AcceptSepaAgreement');
            }
        }

        return $return;
    }

    public function savePaymentData($userId, \Enlight_Controller_Request_Request $request)
    {
        $paymentData = $request->getParam('ratepay');
        $installmentData = $paymentData['installment'];
        $this->isBankDataRequired = PaymentSubType::getPayTypByFirstPayDay($installmentData['payment_firstday']) === PaymentSubType::PAY_TYPE_DIRECT_DEBIT;

        parent::savePaymentData($userId, $request);

        $paymentMethod = $this->sessionHelper->getPaymentMethod();
        $billingAddress = $this->sessionHelper->getBillingAddress();
        $this->installmentService->initInstallmentData(
            $billingAddress->getCountry()->getIso(),
            Shopware()->Shop()->getId(),
            $paymentMethod->getName(),
            false,
            floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']),
            $installmentData['type'],
            $installmentData['payment_firstday'],
            $installmentData['value']
        );
        $this->sessionHelper->setData('installment_calculator_input', $installmentData);
    }
}
