<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;


use Enlight\Event\SubscriberInterface;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\Component\InstallmentCalculator\Service\SessionHelper as InstallmentSessionHelper;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\MessageManager;
use Shopware\Models\Payment\Payment;
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
    /**
     * @var InstallmentSessionHelper
     */
    private $installmentSessionHelper;

    public function __construct(
        SessionHelper      $sessionHelper,
        InstallmentSessionHelper $installmentSessionHelper,
        InstallmentService $installmentService,
        MessageManager     $messageManager
    )
    {
        $this->sessionHelper = $sessionHelper;
        $this->installmentService = $installmentService;
        $this->messageManager = $messageManager;
        $this->installmentSessionHelper = $installmentSessionHelper;
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

        $billingAddress = $this->sessionHelper->getBillingAddress();
        $shippingAddress = $this->sessionHelper->getShippingAddress() ?: $billingAddress;

        $totalAmount = floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']); // TODO no static access!
        $paymentSearch = (new PaymentConfigSearch())
            ->setPaymentMethod($paymentMethod)
            ->setBackend(false)
            ->setBillingCountry($billingAddress->getCountry()->getIso())
            ->setShippingCountry($shippingAddress->getCountry()->getIso())
            ->setShop(Shopware()->Shop())
            ->setCurrency(Shopware()->Config()->get('currency'));;
        $templateVars = $this->installmentService->getInstallmentCalculatorVars(new InstallmentCalculatorContext($paymentSearch, $totalAmount));

        $view->assign('ratepay', array_merge($view->getAssign('ratepay') ?: [], $templateVars));
    }

    public function addInstallmentPlanHtml(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if ($response->isException() || $response->isRedirect()) {
            return;
        }

        $data = [];
        if ($request->getActionName() === 'confirm') {
            $paymentMethod = $this->sessionHelper->getPaymentMethod();
            if (PaymentMethods::isInstallment($paymentMethod) === false) {
                return;
            }

            $requestData = $this->installmentSessionHelper->getRequestData();
            if (!$requestData) {
                // seems like that the customer did not configured his installment plan
                $controller->redirect('checkout/shippingPayment');
                return;
            }

            try {
                $planData = $this->updateInstallmentPlan($requestData, $paymentMethod);
            } catch (\Exception $e) {
                // if the calculation fails, the customer must select another payment method
                $controller->redirect('checkout/shippingPayment');
                return;
            }

            $installmentPlanHtml = $this->installmentService->getInstallmentPlanTemplate($planData);
            $data['installmentPlan'] = $installmentPlanHtml;
        }

        $view->assign('ratepay', array_merge($view->getAssign('ratepay') ?: [], $data));
    }


    /**
     * @param array{total_amount: float, calculation_type: string, calculation_value:int|float} $requestData
     * @param string|int|Payment $paymentMethod
     * @return array{total_amount: float, calculation_type: string, calculation_value:int|float}|null|null
     * @throws \RatePAY\Exception\RequestException
     */
    private function updateInstallmentPlan($requestData, $paymentMethod)
    {
        $sessionTotalAmount = $this->sessionHelper->getSession()->get('sOrderVariables')['sAmount'];
        if ($sessionTotalAmount != $requestData['total_amount']) {
            // ups! the calculated plan does not have the same amount as the shopping cart.
            // so we will recalculate the plan

            $billingAddress = $this->sessionHelper->getBillingAddress();
            $shippingAddress = $this->sessionHelper->getShippingAddress() ?: $billingAddress;

            $paymentConfigSearch = (new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethod)
                ->setBackend(false)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop(Shopware()->Shop()->getId())
                ->setCurrency(Shopware()->Config()->get('currency'));

            $planResult = $this->installmentService->getInstallmentPlan(new InstallmentCalculatorContext(
                $paymentConfigSearch,
                $sessionTotalAmount,
                $requestData['calculation_type'],
                $requestData['calculation_value']
            ));
            $this->installmentSessionHelper->setPlanResult($planResult);

            $this->messageManager->addInfoMessage('InstallmentPlanMayUpdated');

            return $planResult->getPlanData();
        } else {
            return $this->installmentSessionHelper->getPlanData();
        }
    }
}
