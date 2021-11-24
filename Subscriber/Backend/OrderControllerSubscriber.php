<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Monolog\Logger;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\Factory\PaymentRequestDataFactory;
use RpayRatePay\Services\Request\PaymentConfirmService;
use RpayRatePay\Services\Request\PaymentRequestService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Backend_SwagBackendOrder;
use SwagBackendOrder\Components\Order\Hydrator\OrderHydrator;
use SwagBackendOrder\Components\Order\Validator\OrderValidator;

class OrderControllerSubscriber implements SubscriberInterface
{
    /** @var ConfigService */
    protected $config;

    /** @var DfpService */
    protected $dfpService;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var OrderHydrator
     */
    protected $orderHydrator;
    /**
     * @var OrderValidator
     */
    protected $orderValidator;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var PaymentRequestDataFactory
     */
    protected $paymentRequestDataFactory;
    /**
     * @var PaymentConfirmService
     */
    protected $paymentConfirmService;
    /**
     * @var PaymentRequestService
     */
    protected $paymentRequestService;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var \Enlight_Components_Snippet_Manager
     */
    private $snippetManager;

    public function __construct(
        ModelManager                        $modelManager,
        \Enlight_Components_Snippet_Manager $snippetManager,
        ConfigService                       $config,
        SessionHelper                       $sessionHelper,
        DfpService                          $dfpService,
        PaymentRequestDataFactory           $paymentRequestDataFactory,
        PaymentRequestService               $paymentRequestService,
        PaymentConfirmService               $paymentConfirmService,
        Logger                              $logger,
        OrderHydrator                       $orderHydrator = null,
        OrderValidator                      $orderValidator = null
    )
    {
        $this->modelManager = $modelManager;
        $this->orderHydrator = $orderHydrator;
        $this->orderValidator = $orderValidator;
        $this->config = $config;
        $this->paymentRequestDataFactory = $paymentRequestDataFactory;
        $this->dfpService = $dfpService;
        $this->logger = $logger;
        $this->paymentRequestService = $paymentRequestService;
        $this->paymentConfirmService = $paymentConfirmService;
        $this->sessionHelper = $sessionHelper;
        $this->snippetManager = $snippetManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_SwagBackendOrder::createOrderAction::replace' => 'replaceCreateOrderAction',
        ];
    }

    public function replaceCreateOrderAction(Enlight_Hook_HookArgs $args)
    {
        $this->validateDependencies();

        /** @var Shopware_Controllers_Backend_SwagBackendOrder $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();
        $view = $subject->View();

        try {
            $orderStruct = $this->orderHydrator->hydrateFromRequest($request);

            $paymentMethod = $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
            $customer = $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());

            if (PaymentMethods::exists($paymentMethod) === false) {
                // is not a ratepay order
                $this->forwardToSWAGBackendOrders($args);
                return;
            }

            $swagValidations = $this->orderValidator->validate($orderStruct);
            if ($swagValidations->getMessages()) {
                $this->fail($view, $swagValidations->getMessages());
                return;
            }

            $paymentRequestData = $this->paymentRequestDataFactory->createFromOrderStruct(
                $orderStruct,
                [
                    'customer' => $customer,
                    'paymentMethod' => $paymentMethod
                ]
            );

            if (PaymentMethods::isInstallment($paymentMethod) && $paymentRequestData->getInstallmentDetails() === null) {
                $this->fail($view, [$this->snippetManager->getNamespace('backend/ratepay/messages')->get('MissingInstallment')]);
                return;
            }

            $this->paymentRequestService->setPaymentRequestData($paymentRequestData);
            $this->paymentRequestService->setIsBackend(true);
            $paymentResponse = $this->paymentRequestService->doRequest();

            if ($paymentResponse->getResponse()->isSuccessful()) {

                // we need to destroy0 the session data BEFORE the order got created, cause the backend-orders module will create a
                // frontend-session during order creation, and this will destroy the ratepay backend-session data.
                // after that we can not access/destroy the ratepay session data, cause we are already in a frontend-session.
                // this is a very bad bug of the backend-order module ...
                // relates to RATEPLUG-144
                $this->sessionHelper->cleanUp();

                //let SWAG write order to db
                $this->forwardToSWAGBackendOrders($args);
                $orderId = $view->getAssign('orderId');

                if (empty($orderId)) {
                    if ($message = $view->getAssign('message')) {
                        $this->fail($view, [$message]);
                    }
                    return;
                }

                /** @var Order $order */
                $order = $this->modelManager->find(Order::class, $orderId);

                //write order & payment information to the database
                $this->paymentRequestService->completeOrder($order, $paymentResponse);

                //confirm the payment
                $this->paymentConfirmService->setOrder($order);
                $paymentResponse = $this->paymentConfirmService->doRequest();

                if ($paymentResponse->getResponse()->isSuccessful() === false) {
                    $customerMessage = $paymentResponse->getResponse()->getCustomerMessage() . ' (' . $paymentResponse->getResponse()->getReasonMessage() . ')';
                    $this->fail($view, [$customerMessage]);
                }
            } else {
                $customerMessage = $paymentResponse->getResponse()->getCustomerMessage() . ' (' . $paymentResponse->getResponse()->getReasonMessage() . ')';
                $this->fail($view, [$customerMessage]);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());

            $this->fail($view, [$e->getMessage()]);
        }
    }

    private function forwardToSWAGBackendOrders(Enlight_Hook_HookArgs $hookArgs)
    {
        $subject = $hookArgs->getSubject();
        $parentReturn = $subject->executeParent(
            $hookArgs->getMethod(),
            $hookArgs->getArgs()
        );
        $hookArgs->setReturn($parentReturn);
    }

    private function fail($view, $messages)
    {
        $view->assign([
            'success' => false,
            'violations' => $messages,
        ]);
    }

    protected function validateDependencies()
    {
        if ($this->orderHydrator == null || $this->orderValidator == null) {
            throw new Exception('Please install the plugin "SwagBackendOrders" by Shopware');
        }
    }
}
