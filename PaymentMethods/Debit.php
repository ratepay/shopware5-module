<?php


namespace RpayRatePay\PaymentMethods;


use RpayRatePay\Component\Service\ValidationLib;

class Debit extends AbstractPaymentMethod
{

    public function validate($paymentData)
    {
        $return = parent::validate($paymentData);
        $bankAccount = $paymentData['ratepay']['bank_account'];

        if(!isset($bankAccount['iban'])) {
            $return['sErrorMessages'][] = 'Please insert a IBAN';//TODO translation
        }
        $isIban = true;
        $bankAccount['iban'] = trim(str_replace(' ', '', $bankAccount['iban']));
        $bankAccount['bankCode'] = trim(str_replace(' ', '', $bankAccount['bankCode']));

        if(is_numeric($bankAccount['iban'])) {
            $isIban = false;
        } else if(ValidationLib::isIbanValid($bankAccount['iban']) === false) {
            $isIban = true;
            $return['sErrorMessages'][] = 'Please verify your IBAN'; // TODO translate
        }

        if($isIban == false && (!isset($bankAccount['bankCode']) || is_numeric($bankAccount['bankCode']) === false)) {
            $return['sErrorMessages'][] = 'Please insert a valid bank code'; // TODO translate
        }

        return $return;
    }

    public function savePaymentData($userId, \Enlight_Controller_Request_Request $request)
    {
        parent::savePaymentData($userId, $request);
        $paymentData = $request->getParam('ratepay');
        $bankAccount = $paymentData['bank_account'];

        $bankAccount['iban'] = trim(str_replace(' ', '', $bankAccount['iban']));
        $bankAccount['bankCode'] = trim(str_replace(' ', '', $bankAccount['bankCode']));

        $this->sessionHelper->setBankData(
            $userId,
            $bankAccount['iban'],
            $bankAccount['bankCode']
        );
    }

    public function getCurrentPaymentDataAsArray($userId)
    {
        $data = parent::getCurrentPaymentDataAsArray($userId);

        $billingAddress = $this->sessionHelper->getBillingAddress();
        $bankData = $this->sessionHelper->getBankData($billingAddress);

        $data['ratepay']['bank_account'] = [
            'account_holder' => $bankData && $bankData->getAccountHolder() ? $bankData->getAccountHolder() : $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'iban' => $bankData ? ($bankData->getAccountNumber() ? : $bankData->getIban()) : null,
            'bankCode' => $bankData ? $bankData->getBankCode() : null
        ];
        return $data;
    }

}
