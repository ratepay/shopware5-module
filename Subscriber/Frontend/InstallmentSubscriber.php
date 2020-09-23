<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;


use Enlight\Event\SubscriberInterface;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\InstallmentService;
use RpayRatePay\Services\MessageManager;
use Shopware_Controllers_Frontend_Checkout;

class InstallmentSubscriber implements SubscriberInterface
{

    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var InstallmentService
     */
    private $installmentService;
    /**
     * @var MessageManager
     */
    private $messageManager;

    public function __construct(
        SessionHelper $sessionHelper,
        InstallmentService $installmentService,
        MessageManager $messageManager
    )
    {
        $this->sessionHelper = $sessionHelper;
        $this->installmentService = $installmentService;
        $this->messageManager = $messageManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'addInstallmentPlanHtml',
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'addInstallmentCalculatorHtml'
        ];
    }

    public function addInstallmentCalculatorHtml(\Enlight_Hook_HookArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if ($response->isException() || $this->sessionHelper->getCustomer() === null) {
            // an exception occurred or the session has been expired
            return;
        }
        $sUserData = $view->getAssign('sUserData');
        if (isset($sUserData['additional']['payment']['name'])) {
            // this is a little bit tricky. Shopware set another ID to the data object. To use this value is a
            // little bit safer cause related to this value the payment section will show
            $paymentMethod = $sUserData['additional']['payment']['name'];
        } else {
            $paymentMethod = $this->sessionHelper->getPaymentMethod();
        }
        if (PaymentMethods::isInstallment($paymentMethod) === false) {
            return;
        }

        $data = [];
        $billingAddress = $this->sessionHelper->getBillingAddress();

        $totalAmount = floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']); // TODO no static access!
        $htmlCalculator = $this->installmentService->getInstallmentCalculatorTemplate(
            $billingAddress,
            Shopware()->Shop()->getId(),
            $paymentMethod,
            false,
            $totalAmount,
            $view->getAssign()
        );

        $data['installmentCalculator'] = $htmlCalculator;

        $view->assign('ratepay', array_merge($view->getAssign('ratepay') ?: [], $data));
    }

    public function addInstallmentPlanHtml(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if ($response->isException()) {
            return;
        }

        $data = [];
        if ($request->getActionName() === 'confirm') {
            $paymentMethod = $this->sessionHelper->getPaymentMethod();
            if (PaymentMethods::isInstallment($paymentMethod) === false) {
                return;
            }
            $billingAddress = $this->sessionHelper->getBillingAddress();
            $dto = $this->sessionHelper->getInstallmentRequestDTO();
            try {
                //Update installment plan
                $this->updateInstallmentPlan($dto, $paymentMethod);
            } catch (\Exception $e) {
                // if the calculation fails, the customer must select another payment method
                $controller->redirect('checkout/shippingPayment');
                return;
            }


            $installmentPlanHtml = $this->installmentService->getInstallmentPlanTemplate(
                $billingAddress,
                Shopware()->Shop()->getId(),
                $paymentMethod,
                false,
                $this->sessionHelper->getInstallmentRequestDTO()
            );
            $data['installmentPlan'] = $installmentPlanHtml;
        }

        $view->assign('ratepay', array_merge($view->getAssign('ratepay') ?: [], $data));
    }


    public function updateInstallmentPlan(InstallmentRequest $dto, $paymentMethod)
    {
        $sessionTotalAmount = $this->sessionHelper->getSession()->get('sOrderVariables')['sAmount'];
        $billingAddress = $this->sessionHelper->getBillingAddress();
        if ($sessionTotalAmount != $dto->getTotalAmount()) {
            // ups! the calculated plan does not have the same amount as the shopping cart ...
            $dto->setTotalAmount($sessionTotalAmount);
            // try to recalculate it.
            $this->installmentService->initInstallmentData(
                $billingAddress,
                Shopware()->Shop()->getId(),
                $paymentMethod,
                false,
                $dto
            );
            $this->messageManager->addInfoMessage('InstallmentPlanMayUpdated');
        }

    }
}
