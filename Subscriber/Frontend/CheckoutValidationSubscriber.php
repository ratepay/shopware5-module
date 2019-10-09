<?php

namespace RpayRatePay\Subscriber\Frontend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use Monolog\Logger;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Frontend_Checkout;

class CheckoutValidationSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        ModelManager $modelManager,
        SessionHelper $sessionHelper,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->logger = $logger;
        $this->sessionHelper = $sessionHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'preValidation',
            'Shopware_Modules_Admin_InitiatePaymentClass_AddClass' => 'addPaymentMethodClasses'
        ];
    }

    public function addPaymentMethodClasses(\Enlight_Event_EventArgs $args)
    {
        $classes = $args->getReturn();
        foreach(PaymentMethods::PAYMENTS as $name => $method) {
            $classes[$name] = $method['real_class'];
        }
        $args->setReturn($classes);
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
        if ($request->getControllerName() !== 'checkout' || $request->getActionName() !== 'shippingPayment') {
            return;
        }

        $customer = $this->sessionHelper->getCustomer();
        if($customer == null) {
            $this->logger->error('Customer can not be loaded');
            return;
        }
        $billingAddress = $this->sessionHelper->getBillingAddress($customer);
        $shippingAddress = $this->sessionHelper->getShippingAddress($customer);
        $paymentMethod = $this->sessionHelper->getPaymentMethod($customer);

        if (PaymentMethods::exists($paymentMethod)) {
            //$ratePaySession = $this->session->RatePAY;

            $view->assign([
                'sRegisterFinished' => 'false',
                'ratepay' => [
                    'validation' => [
                        'isBirthdayRequired' => ValidationService::isCompanySet($billingAddress) === false,
                        'isB2B' => ValidationService::isCompanySet($billingAddress),
                        'isBillingAddressSameLikeShippingAddress' => ValidationService::areBillingAndShippingSame($billingAddress, $shippingAddress),
                        'isBirthdayValid' => ValidationService::isBirthdayValid($customer, $billingAddress),
                        'isAgeValid' => ValidationService::isBirthdayValid($customer, $billingAddress),
                    ],
                    'ratenrechner' => [
                        //TODO ratenrechner
                        //$view->assign('errorRatenrechner', (!$ratePaySession['errorRatenrechner']) ? 'false' : $ratePaySession['errorRatenrechner']);
                    ],
                ]
            ]);
        }
    }

}
