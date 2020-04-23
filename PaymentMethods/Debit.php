<?php


namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;
use RpayRatePay\Component\Service\ValidationLib;

class Debit extends AbstractPaymentMethod
{

    protected $isBankDataRequired = true;

    public function getCurrentPaymentDataAsArray($userId)
    {
        $data = parent::getCurrentPaymentDataAsArray($userId);
        if ($this->isBankDataRequired === false) {
            return $data;
        }
        $billingAddress = $this->sessionHelper->getBillingAddress();
        $bankData = $this->sessionHelper->getBankData($billingAddress);

        $data['ratepay']['bank_account'] = [
            'account_holder' => $bankData && !empty($bankData->getAccountHolder()) ? $bankData->getAccountHolder() : $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'iban' => $bankData ? ($bankData->getAccountNumber() ?: $bankData->getIban()) : null,
            'bankCode' => $bankData ? $bankData->getBankCode() : null,
        ];

        // this is just a fix, cause we will not save this value. But if the payment data got validated,
        // we will validate against this field.
        $data['ratepay']['sepa_agreement'] = true;
        return $data;
    }

    protected function _validate($paymentData)
    {
        $return = parent::_validate($paymentData);
        if ($this->isBankDataRequired === false) {
            return $return;
        } else if (!isset($paymentData['ratepay']['sepa_agreement'])) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('AcceptSepaAgreement');
        }
        $bankAccount = $paymentData['ratepay']['bank_account'];

        if (!isset($bankAccount['iban'])) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingIban');
        }
        $isIban = true;
        $bankAccount['iban'] = trim(str_replace(' ', '', $bankAccount['iban']));
        $bankAccount['bankCode'] = trim(str_replace(' ', '', $bankAccount['bankCode']));

        if (is_numeric($bankAccount['iban'])) {
            $isIban = false;
        } else if (ValidationLib::isIbanValid($bankAccount['iban']) === false) {
            $isIban = true;
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidIban');
        }

        if ($isIban === false && (!isset($bankAccount['bankCode']))) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingBankCode');
        } else if ($isIban === false && is_numeric($bankAccount['bankCode']) === false) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidBankCode');
        }

        return $return;
    }

    protected function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        if ($this->isBankDataRequired === false) {
            $this->sessionHelper->setBankData($userId, null);
            return;
        }
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

}
