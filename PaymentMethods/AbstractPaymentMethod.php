<?php


namespace RpayRatePay\PaymentMethods;


use DateTime;
use Enlight_Controller_Request_Request;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Helper\SessionHelper;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;

class AbstractPaymentMethod extends GenericPaymentMethod
{

    /**
     * @var Container
     */
    protected $container;
    /**
     * @var object|SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var ModelManager
     */
    protected $modelManager;


    private $snippetManager;

    public function __construct()
    {
        $this->container = Shopware()->Container();
        $this->sessionHelper = $this->container->get(SessionHelper::class);
        $this->modelManager = $this->container->get('models');
        $this->snippetManager = $this->container->get('snippets');
    }

    public function getCurrentPaymentDataAsArray($userId)
    {
        $data = parent::getCurrentPaymentDataAsArray($userId);
        $customer = $this->sessionHelper->getCustomer();
        if ($customer == null || $customer->getId() !== (int)$userId) {
            return [];
        }
        $billingAddress = $this->sessionHelper->getBillingAddress($customer);

        /** @var DateTime $birthday */
        $birthday = $customer->getBirthday();

        $data['ratepay']['customer_data'] = [
            'phone' => $billingAddress->getPhone(),
            'birthday_required' => ValidationLib::isCompanySet($billingAddress) === false,
            'birthday' => [
                'year' => $birthday ? $birthday->format('Y') : null,
                'month' => $birthday ? $birthday->format('m') : null,
                'day' => $birthday ? $birthday->format('d') : null
            ]
        ];
        return $data;
    }

    public function validate($paymentData)
    {
        $return = [];
        $ratepayData = $paymentData['ratepay']['customer_data'];
        if (!isset($ratepayData['birthday_required']) || $ratepayData['birthday_required'] == 1) {
            if (!isset($ratepayData['birthday'])) {
                $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingBirthday');
            } else {
                $dateTime = new DateTime();
                $dateTime->setDate($ratepayData['birthday']['year'], $ratepayData['birthday']['month'], $ratepayData['birthday']['day']);
                if (ValidationLib::isOldEnough($dateTime) == false) {
                    $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidBirthday'), 18); //TODO config?
                }
            }
        }

        if (!isset($ratepayData['phone'])) {
            $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingPhone');
        }
        if ((strlen(trim($ratepayData['phone'])) > 6) === false) {
            $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidPhone'), 6); //TODO config?
        }
        return $return;
    }

    public function savePaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        $ratepayData = $request->getParam('ratepay')['customer_data'];

        $birthday = new DateTime();
        $birthday->setDate(
            trim($ratepayData['birthday']['year']),
            trim($ratepayData['birthday']['month']),
            trim($ratepayData['birthday']['day'])
        );
        $customer = $this->sessionHelper->getCustomer();
        $billingAddress = $this->sessionHelper->getBillingAddress();
        $customer->setBirthday($birthday);
        $billingAddress->setPhone(trim($ratepayData['phone']));

        $this->modelManager->flush([$customer, $billingAddress]);
    }

    protected function getTranslatedMessage($snippetName)
    {
        return $this->snippetManager->getNamespace('frontend/ratepay/messages')->get($snippetName);
    }
}
