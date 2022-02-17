<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\PaymentMethods;


use DateTime;
use Enlight_Controller_Front;
use Enlight_Controller_Request_Request;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware_Components_Snippet_Manager;
use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;

abstract class AbstractPaymentMethod extends GenericPaymentMethod
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

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;
    /**
     * @var Enlight_Controller_Front
     */
    private $front;
    /**
     * @var ConfigService
     */
    private $config;

    public function __construct()
    {
        $this->container = Shopware()->Container();
        $this->sessionHelper = $this->container->get(SessionHelper::class);
        $this->modelManager = $this->container->get('models');
        $this->snippetManager = $this->container->get('snippets');
        $this->front = $this->container->get('front');
        $this->config = $this->container->get(ConfigService::class);
    }

    protected function isRequestInCheckoutProcess()
    {
        return $this->front->Request()->getControllerName() === 'checkout';
    }

    public function getCurrentPaymentDataAsArray($userId)
    {
        $customer = $this->sessionHelper->getCustomer();
        $billingAddress = $customer ? $this->sessionHelper->getBillingAddress($customer) : null;

        // this can occur, if the customer want's to calculate the shipping costs in the cart
        // please also see RATEPLUG-180
        if ($customer === null || $billingAddress === null || $customer->getId() !== (int)$userId) {
            return null;
        }

        $data = parent::getCurrentPaymentDataAsArray($userId);

        /** @var DateTime $birthday */
        $birthday = $customer->getBirthday();

        $data['ratepay']['customer_data'] = [
            'phone' => $billingAddress->getPhone(),
            'phone_required' => $this->config->isUserInputPhoneNumberRequired(),
            'phone_visible' => $this->config->isUserInputPhoneNumberVisible(),
            'birthday_required' => ValidationLib::isCompanySet($billingAddress) === false,
            'birthday' => [
                'year' => $birthday ? $birthday->format('Y') : null,
                'month' => $birthday ? $birthday->format('m') : null,
                'day' => $birthday ? $birthday->format('d') : null
            ],
            'vatId_required' => ValidationLib::isCompanySet($billingAddress) === true,
            'vatId' => $billingAddress->getVatId()
        ];
        return $data;
    }

    protected function _validate($paymentData)
    {
        $return = [];
        if ($this->sessionHelper->getCustomer() === null) {
            // customer is not logged in - maybe session has been expired
            $return['sErrorMessages'][] = 'Please login';
            return $return;
        }

        $ratepayData = $paymentData['ratepay']['customer_data'];
        if (!isset($ratepayData['birthday_required']) || $ratepayData['birthday_required'] == 1) {
            if (!isset($ratepayData['birthday'])) {
                $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingBirthday');
            } else if (checkdate($ratepayData['birthday']['month'], $ratepayData['birthday']['day'], $ratepayData['birthday']['year']) === false) {
                $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidDateFormatBirthday'));
            } else {
                $dateTime = new DateTime();
                $dateTime->setDate($ratepayData['birthday']['year'], $ratepayData['birthday']['month'], $ratepayData['birthday']['day']);
                if (ValidationLib::isOldEnough($dateTime) === false) {
                    $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidBirthday'), 18); //TODO config?
                }
            }
        }

        if ($this->config->isUserInputPhoneNumberRequired()) {
            if (!isset($ratepayData['phone']) || empty(trim($ratepayData['phone']))) {
                $return['sErrorMessages'][] = $this->getTranslatedMessage('MissingPhone');
            }
            if ((strlen(trim($ratepayData['phone'])) > 6) === false) {
                $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidPhone'), 6);
            }
        }

        $billingAddress = $this->sessionHelper->getBillingAddress();
        if (isset($ratepayData['vatId_required'], $ratepayData['vatId']) && ((int)$ratepayData['vatId_required']) === 1) {
            $ratepayData['vatId'] = trim($ratepayData['vatId']);
            if ($billingAddress &&
                !empty($ratepayData['vatId']) &&
                ValidationLib::isVatIdValid($billingAddress->getCountry()->getIso(), $ratepayData['vatId']) === false
            ) {
                $vatPrefix = ValidationLib::VAT_REGEX[$billingAddress->getCountry()->getIso()]['prefix'];
                if (count($vatPrefix) > 1) {
                    $textOr = $this->snippetManager->getNamespace('frontend/ratepay')->get('or');
                    $lastIndex = count($vatPrefix) - 1;
                    $lastItem = $vatPrefix[$lastIndex];
                    unset($vatPrefix[$lastIndex]);
                    $vatPrefix[$lastIndex - 1] .= ' ' . $textOr . ' ' . $lastItem;
                }
                $return['sErrorMessages'][] = sprintf($this->getTranslatedMessage('InvalidVatId'), implode(', ', $vatPrefix));
            }
        }

        return $return;
    }

    public final function validate($paymentData)
    {
        if ($this->isRequestInCheckoutProcess()) {
            return $this->_validate($paymentData);
        }
        return [];
    }

    protected function getTranslatedMessage($snippetName)
    {
        return $this->snippetManager->getNamespace('frontend/ratepay/messages')->get($snippetName);
    }

    public final function savePaymentData($userId, Enlight_Controller_Request_Request $request)
    {
        if ($this->isRequestInCheckoutProcess()) {
            // firstly delete all previous saved data. maybe the customer has canceled
            // a payment and now switched to another payment method.
            $this->sessionHelper->cleanUp();

            $ratepayData = $request->getParam('ratepay')['customer_data'];

            $customer = $this->sessionHelper->getCustomer();
            $billingAddress = $this->sessionHelper->getBillingAddress();


            if (!$billingAddress->getCompany() || empty($billingAddress->getCompany())) {
                $birthday = new DateTime();
                $birthday->setDate(
                    trim($ratepayData['birthday']['year']),
                    trim($ratepayData['birthday']['month']),
                    trim($ratepayData['birthday']['day'])
                );

                // maybe it would be better to save the value in a attribute, to not override the real customer data.
                $customer->setBirthday($birthday);
            }

            $ratepayData['phone'] = trim($ratepayData['phone']);
            if (!empty($ratepayData['phone'])) {
                // maybe it would be better to save the value in a attribute, to not override the real customer data.
                $billingAddress->setPhone($ratepayData['phone']);
            }

            if (isset($ratepayData['vatId']) && !empty($ratepayData['vatId'])) {
                // maybe it would be better to save the value in a attribute, to not override the real customer data.
                $ratepayData['vatId'] = trim($ratepayData['vatId']);
                $billingAddress->setVatId($ratepayData['vatId']);
            }

            $this->modelManager->flush([$customer, $billingAddress]);
            $this->saveRatePayPaymentData($userId, $request);
        }
    }

    protected abstract function saveRatePayPaymentData($userId, Enlight_Controller_Request_Request $request);

    protected function getPaymentMethodFromRequest(Enlight_Controller_Request_Request $request)
    {
        return $this->modelManager->find(Payment::class, $request->getParam('payment'));
    }
}
