<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Service\Logger;

class CheckoutValidationSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'preValidation',
        ];
    }

    /**
     * validated the Userdata
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function preValidation(\Enlight_Event_EventArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $response = $arguments->getSubject()->Response();
        $view = $arguments->getSubject()->View();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
            || !$view->hasTemplate()) {
            return;
        }

        // Check for the right action and controller
        if ($request->getControllerName() !== 'checkout' || $request->getActionName() !== 'confirm') {
            return;
        }

        $userId = Shopware()->Session()->sUserId;
        if (empty($userId)) {
            Logger::singleton()->warning('RatePAY: sUserId is empty');
            return;
        }

        Shopware()->Template()->addTemplateDir($this->path . 'Views/');
        $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $userId);
        $paymentType = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());

        $validation = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($user, $paymentType);

        if ($validation->isRatePAYPayment()) {
            $view->sRegisterFinished = 'false';

            $view->ratepayValidateCompanyName = $validation->isCompanyNameSet() ? 'true' : 'false';
            Logger::singleton()->info('RatePAY: isCompanyNameSet->' . $view->ratepayValidateCompanyName);

            $view->ratepayValidateIsB2B = $validation->isCompanyNameSet() ? 'true' : 'false';
            Logger::singleton()->info('RatePAY: isB2B->' . $view->ratepayValidateIsB2B);

            $view->ratepayIsBillingAddressSameLikeShippingAddress = $validation->isBillingAddressSameLikeShippingAddress() ? 'true' : 'false';
            Logger::singleton()->info('RatePAY: isBillingAddressSameLikeShippingAddress->' . $view->ratepayIsBillingAddressSameLikeShippingAddress);

            if ($view->ratepayValidateIsB2B === false) {
                $view->ratepayValidateIsBirthdayValid = $validation->isBirthdayValid();
                $view->ratepayValidateisAgeValid = $validation->isAgeValid();
            } else {
                $view->ratepayValidateIsBirthdayValid = true;
                $view->ratepayValidateisAgeValid = true;
            }

            $view->errorRatenrechner = (!Shopware()->Session()->RatePAY['errorRatenrechner']) ? 'false' : Shopware()->Session()->RatePAY['errorRatenrechner'];
        }
    }
}
