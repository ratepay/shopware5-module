<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\Component\InstallmentCalculator\Service\SessionHelper;
use RpayRatePay\Component\InstallmentCalculator\Service\SessionHelper as InstallmentSessionHelper;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentFirstDay;

class Installment extends Debit
{

    //we need this to prevent that the installment0 got not the payment data from THIS installment
    protected $formDataKey = 'installment';

    /**
     * @var object|InstallmentService
     */
    private $installmentService;

    /**
     * @var InstallmentSessionHelper
     */
    private $installmentSessionHelper;

    public function __construct()
    {
        parent::__construct();
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->installmentSessionHelper = $this->container->get(SessionHelper::class);
    }

    public function getCurrentPaymentDataAsArray($userId)
    {
        $data = parent::getCurrentPaymentDataAsArray($userId);
        if ($data === null) {
            // if parent method "failed" with `null`, this method has to be "failed", too.
            return null;
        }

        $installmentData = $this->installmentSessionHelper->getRequestData();
        if ($installmentData) {
            $installmentData['payment_type'] = $this->installmentSessionHelper->getPaymentType();
            $this->isBankDataRequired = $this->installmentSessionHelper->getPaymentType() !== PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;
            $data['ratepay'][$this->formDataKey] = $installmentData;
        }

        return $data;
    }

    protected function _validate($paymentData)
    {
        $installmentData = isset($paymentData['ratepay'][$this->formDataKey]) ? $paymentData['ratepay'][$this->formDataKey] : [];
        $this->isBankDataRequired = $paymentData['paymentType'] === PaymentFirstDay::PAY_TYPE_DIRECT_DEBIT;

        $return = parent::_validate($paymentData);

        if ($installmentData === null ||
            !isset($installmentData['calculation_type'], $installmentData['calculation_value'], $installmentData['payment_type']) ||
            empty($installmentData['calculation_type']) ||
            empty($installmentData['calculation_value']) ||
            empty($installmentData['payment_type'])
        ) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidCalculator');
        }

        return $return;
    }

    protected function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        $paymentData = $request->getParam('ratepay');
        $installmentData = $paymentData[$this->formDataKey];
        $this->isBankDataRequired = $installmentData['payment_type'] !== PaymentFirstDay::PAY_TYPE_BANK_TRANSFER;

        parent::saveRatePayPaymentData($userId, $request);

        $paymentMethod = $this->getPaymentMethodFromRequest($request);

        $this->installmentService->initInstallmentData(new InstallmentCalculatorContext(
            $this->sessionHelper->getPaymentConfigSearchObject($paymentMethod),
            $request->getParam('rp-calculation-amount'),
            $installmentData['calculation_type'],
            $installmentData['calculation_value']
        ));
        $this->installmentSessionHelper->setPaymentType($installmentData['payment_type']);
    }

}
