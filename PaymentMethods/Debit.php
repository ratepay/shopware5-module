<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Util\BankDataUtil;

class Debit extends AbstractPaymentMethod
{

    protected $isBankDataRequired = true;

    public function getCurrentPaymentDataAsArray($userId)
    {
        $data = parent::getCurrentPaymentDataAsArray($userId);

        // if parent method "failed" with `null`, this method has to be "failed", too.
        if ($this->isBankDataRequired === false || $data === null) {
            return $data;
        }
        $billingAddress = $this->sessionHelper->getBillingAddress();

        /** @noinspection NullPointerExceptionInspection */ // has been already caught in parent method
        $bankData = $this->sessionHelper->getBankData($billingAddress);

        /** @noinspection NullPointerExceptionInspection */ // has been already caught in parent method
        $accountHolders = BankDataUtil::getAvailableAccountHolder($billingAddress, $bankData);
        $data['ratepay']['bank_account'] = [
            'accountHolder' => [
                'list' => $accountHolders,
                'selected' => $bankData && $bankData->getAccountHolder() ? $bankData->getAccountHolder() : $accountHolders[0]
            ],
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

        $billingAddress = $this->sessionHelper->getBillingAddress();
        $accountHolders = $billingAddress ? BankDataUtil::getAvailableAccountHolder($billingAddress, $this->sessionHelper->getBankData($billingAddress)) : null;
        if (!isset($bankAccount['accountHolder']['selected']) || $accountHolders === null) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingAccountHolder');
        } else if (!in_array($bankAccount['accountHolder']['selected'], $accountHolders, true)) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidAccountHolder');
        }

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
            $this->sessionHelper->setBankData($userId);
            return;
        }
        $paymentData = $request->getParam('ratepay');
        $bankAccount = $paymentData['bank_account'];


        $this->sessionHelper->setBankData(
            $userId,
            $bankAccount['accountHolder']['selected'],
            trim(str_replace(' ', '', $bankAccount['iban']))
        );
    }

}
