<?php

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Monolog\Logger;
use RatePAY\Model\Response\PaymentRequest as PaymentResponse;
use RpayRatePay\Enum\PaymentMethods;
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

    public function __construct(
        ModelManager $modelManager,
        OrderHydrator $orderHydrator,
        OrderValidator $orderValidator,
        ConfigService $config,
        DfpService $dfpService,
        PaymentRequestDataFactory $paymentRequestDataFactory,
        PaymentRequestService $paymentRequestService,
        PaymentConfirmService $paymentConfirmService,
        Logger $logger
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
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_SwagBackendOrder::createOrderAction::replace' => 'replaceCreateOrderAction',
        ];
    }

    public function replaceCreateOrderAction(Enlight_Hook_HookArgs $args)
    {
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

            /** @var PaymentResponse $paymentResponse */
            $this->paymentRequestService->setPaymentRequestData($paymentRequestData);
            $this->paymentRequestService->setIsBackend(true);
            $paymentResponse = $this->paymentRequestService->doRequest();

            if ($paymentResponse->isSuccessful()) {
                //let SWAG write order to db
                $this->forwardToSWAGBackendOrders($args);

                /** @var Order $order */
                $order = $this->modelManager->find(Order::class, $view->getAssign('orderId'));

                //write order & payment information to the database
                $this->paymentRequestService->completeOrder($order, $paymentResponse);

                //confirm the payment
                $this->paymentConfirmService->setOrder($order);
                $paymentResponse = $this->paymentConfirmService->doRequest();

                if ($paymentResponse->isSuccessful() === false) {
                    $customerMessage = $paymentResponse->getCustomerMessage();
                    $this->fail($view, [$customerMessage]);
                }
            } else {
                $customerMessage = $paymentResponse->getCustomerMessage();
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
}
