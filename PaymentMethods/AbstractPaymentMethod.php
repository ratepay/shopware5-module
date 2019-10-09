<?php


namespace RpayRatePay\PaymentMethods;


use DateTime;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Helper\SessionHelper;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;

class AbstractPaymentMethod extends GenericPaymentMethod
{

    /**
     * @var object|SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct()
    {
        $this->sessionHelper = Shopware()->Container()->get(SessionHelper::class);
        $this->modelManager = Shopware()->Container()->get('models');
    }

    public function getCurrentPaymentDataAsArray($userId) {
        $customer = $this->sessionHelper->getCustomer();
        if($customer == null || $customer->getId() !== (int) $userId) {
            return [];
        }
        $billingAddress = $this->sessionHelper->getBillingAddress($customer);

        /** @var DateTime $birthday */
        $birthday = $customer->getBirthday();
        return [
            'ratepay' => [
                'customer_data' => [
                    'phone' => $billingAddress->getPhone(),
                    'birthday' => [
                        'year' => $birthday ? $birthday->format('Y') : null,
                        'month' => $birthday ? $birthday->format('m'): null,
                        'day' => $birthday ? $birthday->format('d'): null
                    ]
                ]
            ]
        ];
    }

    public function validate($paymentData)
    {
        $return = [];
        $ratepayData = $paymentData['ratepay']['customer_data'];
        if(!isset($ratepayData['birthday'])) {
            $return['sErrorMessages'][] = 'Please set a birthday';//TODO translation
        }
        if(!isset($ratepayData['phone']) || (strlen(trim($ratepayData['phone'])) > 6) === false) { //TODO config?
            $return['sErrorMessages'][] = 'Please set a phone number';//TODO translation
        }

        $dateTime = new DateTime();
        $dateTime->setDate($ratepayData['birthday']['year'], $ratepayData['birthday']['month'], $ratepayData['birthday']['day']);
        if(ValidationLib::isOldEnough($dateTime) == false) {
            $return['sErrorMessages'][] = 'Please verify your date of birth. You must be at least 18 years old!'; //TODO translation
        }
        return $return;
    }
    public function savePaymentData($userId, \Enlight_Controller_Request_Request $request)
    {
        $ratepayData = $request->getParam('ratepay')['customer_data'];

        $birthday = new DateTime();
        $birthday->setDate(
            trim($ratepayData['birthday']['year']),
            trim($ratepayData['birthday']['month']),
            trim($ratepayData['birthday']['day'])
        );
        $customer = $this->sessionHelper->getCustomer();
        $customer->setBirthday($birthday);
        $customer->getDefaultBillingAddress()->setPhone(trim($ratepayData['phone']));
        if(ValidationLib::isBirthdayValid($customer, $customer->getDefaultBillingAddress()) == false) {
            $return['checkPayment']['sErrorMessages'][] = 'Please verify your date of birth. You must be at least 18 years old!'; //TODO translation
        } else {
            $this->modelManager->flush([$customer, $customer->getDefaultBillingAddress()]);
        }
    }
}
