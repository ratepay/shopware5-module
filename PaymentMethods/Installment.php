<?php


namespace RpayRatePay\PaymentMethods;


use Enlight_Controller_Request_Request;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentSubType;
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
        $installmentData = $this->sessionHelper->getInstallmentRequestDTO();
        $this->isBankDataRequired = $installmentData->getPaymentType() !== PaymentSubType::PAY_TYPE_BANK_TRANSFER;

        $data = parent::getCurrentPaymentDataAsArray($userId);
        $data['ratepay'][$this->formDataKey] = $installmentData->toArray();

        // this is just a fix, cause we will not save this value. But if the payment data got validated,
        // we will validate against this field.
        $data['ratepay'][$this->formDataKey]['sepa_agreement'] = true;
        return $data;
    }

    public function validate($paymentData)
    {
        $installmentData = isset($paymentData['ratepay'][$this->formDataKey]) ? $paymentData['ratepay'][$this->formDataKey] : [];
        $this->isBankDataRequired = $installmentData['paymentType'] !== PaymentSubType::PAY_TYPE_BANK_TRANSFER;

        $return = parent::validate($paymentData);


        if ($installmentData == null ||
            !isset(
                $installmentData['type'],
                $installmentData['value'],
                $installmentData['paymentType'],
                $installmentData['paymentFirstDay']
            ) ||
            empty($installmentData['type']) ||
            empty($installmentData['value']) ||
            empty($installmentData['paymentType']) ||
            empty($installmentData['paymentFirstDay'])
        ) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('InvalidCalculator');
        }

        if ($this->isBankDataRequired) {
            if (!isset($installmentData['sepa_agreement']) || empty($installmentData['sepa_agreement'])) {
                $return['sErrorMessages'][] = $this->getTranslatedMessage('AcceptSepaAgreement');
            }
        }

        return $return;
    }

    protected function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        $paymentData = $request->getParam('ratepay');
        $installmentData = $paymentData[$this->formDataKey];
        $this->isBankDataRequired = $installmentData['paymentType'] !== PaymentSubType::PAY_TYPE_BANK_TRANSFER;

        parent::saveRatePayPaymentData($userId, $request);

        $dto = new InstallmentRequest(
            floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']),
            $installmentData['type'],
            $installmentData['value'],
            $installmentData['paymentType'],
            $installmentData['paymentFirstDay']
        );

        $paymentMethod = $this->getPaymentMethodFromRequest($request);
        $billingAddress = $this->sessionHelper->getBillingAddress();
        $this->installmentService->initInstallmentData(
            $billingAddress,
            Shopware()->Shop()->getId(),
            $paymentMethod->getName(),
            false,
            $dto
        );
    }
}
