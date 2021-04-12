<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Services\InstallmentService;

class Installment extends Debit
{

    //we need this to prevent that the installment0 got not the payment data from THIS installment
    protected $formDataKey = 'installment';

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
        $data = parent::getCurrentPaymentDataAsArray($userId);
        if ($data === null) {
            // if parent method "failed" with `null`, this method has to be "failed", too.
            return null;
        }

        $installmentData = $this->sessionHelper->getInstallmentRequestDTO();
        $this->isBankDataRequired = $installmentData->getPaymentType() !== PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;

        $data['ratepay'][$this->formDataKey] = $installmentData->toArray();

        return $data;
    }

    protected function _validate($paymentData)
    {
        $installmentData = isset($paymentData['ratepay'][$this->formDataKey]) ? $paymentData['ratepay'][$this->formDataKey] : [];
        $this->isBankDataRequired = $installmentData['paymentType'] !== PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;

        $return = parent::_validate($paymentData);

        if ($installmentData == null ||
            !isset(
                $installmentData['type'],
                $installmentData['value'],
                $installmentData['paymentType']
            ) ||
            empty($installmentData['type']) ||
            empty($installmentData['value']) ||
            empty($installmentData['paymentType'])
        ) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidCalculator');
        }

        return $return;
    }

    protected function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        $paymentData = $request->getParam('ratepay');
        $installmentData = $paymentData[$this->formDataKey];
        $this->isBankDataRequired = $installmentData['paymentType'] !== PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;

        parent::saveRatePayPaymentData($userId, $request);

        $dto = new InstallmentRequest(
            floatval($request->getParam('rp-calculation-amount')),
            $installmentData['type'],
            $installmentData['value'],
            $installmentData['paymentType']
        );

        $paymentMethod = $this->getPaymentMethodFromRequest($request);

        $billingAddress = $this->sessionHelper->getBillingAddress();
        $shippingAddress = $this->sessionHelper->getShippingAddress() ?: $billingAddress;

        $this->installmentService->initInstallmentData((new PaymentConfigSearch())
            ->setPaymentMethod($paymentMethod)
            ->setBackend(false)
            ->setBillingCountry($billingAddress->getCountry()->getIso())
            ->setShippingCountry($shippingAddress->getCountry()->getIso())
            ->setShop(Shopware()->Shop()->getId())
            ->setCurrency(Shopware()->Config()->get('currency')),
            $dto
        );
    }

}
