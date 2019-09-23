<?php

namespace RpayRatePay\Subscriber\Frontend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use Monolog\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Frontend_Checkout;
use Shopware_Plugins_Frontend_RpayRatePay_Component_Validation;

class CheckoutValidationSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Session_Namespace $session,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->session = $session;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'preValidation',
        ];
    }

    /**
     * validate the userdata
     *
     * @param Enlight_Event_EventArgs $args
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function preValidation(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();

        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

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

        $userId = $this->session->get('sUserId');
        if (empty($userId)) {
            $this->logger->warning('RatePAY: sUserId is empty');
            return;
        }

        /** @var Customer $user */
        $user = $this->modelManager->find(Customer::class, $userId);
        /** @var Payment $paymentType */
        $paymentType = $this->modelManager->find(Payment::class, $user->getPaymentId());

        $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($user, $paymentType); //TODO service

        if ($validation->isRatePAYPayment()) {
            $ratePaySession = $this->session->RatePAY;

            $view->assign('sRegisterFinished', 'false');
            $view->assign('ratepayValidateCompanyName', $validation->isCompanyNameSet() ? 'true' : 'false');
            $view->assign('atepayValidateIsB2B', $validation->isCompanyNameSet() ? 'true' : 'false');
            $view->assign('ratepayIsBillingAddressSameLikeShippingAddress', $validation->isBillingAddressSameLikeShippingAddress() ? 'true' : 'false');
            $view->assign('ratepayValidateIsBirthdayValid', $validation->isCompanyNameSet() || $validation->isBirthdayValid());
            $view->assign('ratepayValidateisAgeValid', $validation->isCompanyNameSet() || $validation->isAgeValid());
            $view->assign('errorRatenrechner', (!$ratePaySession['errorRatenrechner']) ? 'false' : $ratePaySession['errorRatenrechner']);
        }
    }

}
