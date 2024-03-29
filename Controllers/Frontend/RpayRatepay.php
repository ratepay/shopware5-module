<?php

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use RatePAY\Exception\RequestException;
use RatePAY\Model\Response\PaymentRequest;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\Factory\PaymentRequestDataFactory;
use RpayRatePay\Services\MessageManager;
use RpayRatePay\Services\Request\PaymentConfirmService;
use RpayRatePay\Services\Request\PaymentRequestService;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Order;

class Shopware_Controllers_Frontend_RpayRatepay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /** @var DfpService */
    protected $dfpService;

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var object|PaymentRequestService
     */
    protected $paymentRequestService;
    /**
     * @var object|PaymentRequestDataFactory
     */
    protected $paymentRequestDataFactory;
    /**
     * @var SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var object|InstallmentService
     */
    protected $installmentService;
    /**
     * @var object|ConfigService
     */
    private $configService;
    /**
     * @var PaymentConfirmService
     */
    private $paymentConfirmService;
    /**
     * @var object|ProfileConfigService
     */
    private $profileConfigService;
    /**
     * @var object|MessageManager
     */
    private $messageManager;
    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    public function setContainer(Container $container = null)
    {
        parent::setContainer($container);

        $this->paymentRequestDataFactory = $this->container->get(PaymentRequestDataFactory::class);
        $this->paymentRequestService = $this->container->get(PaymentRequestService::class);
        $this->paymentConfirmService = $this->container->get(PaymentConfirmService::class);
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->logger = $this->container->get('ratepay.logger');
        $this->dfpService = $this->container->get(DfpService::class);
        $this->configService = $this->container->get(ConfigService::class);
        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->sessionHelper = $this->container->get(SessionHelper::class);
        $this->messageManager = $this->container->get(MessageManager::class);
        $this->snippetManager = $this->container->get('snippets');
    }

    /**
     *  Checks the Paymentmethod
     */
    public function indexAction()
    {
        if (!PaymentMethods::exists($this->getPaymentShortName())) {
            $this->redirect(
                Shopware()->Front()->Router()->assemble(
                    [
                        'controller' => 'checkout',
                        'action' => 'confirm',
                        'forceSecure' => true
                    ]
                )
            );
            return;
        }

        Shopware()->Session()->RatePAY['errorRatenrechner'] = 'false';
        $this->_proceedPayment();
    }

    /**
     * Procceds the whole Paymentprocess
     */
    private function _proceedPayment()
    {

        try {
            $this->paymentRequestService->setIsBackend(false);
            $paymentRequestData = $this->paymentRequestDataFactory->createFromFrontendSession();
            $this->paymentRequestService->setPaymentRequestData($paymentRequestData);
            /** @var PaymentRequest $requestResponse */
            $requestResponse = $this->paymentRequestService->doRequest();

            if ($requestResponse->isSuccessful()) {

                $transactionId = $requestResponse->getTransactionId();
                $uniqueId = $this->createPaymentUniqueId();

                $statusId = $this->configService->getPaymentStatusAfterPayment($paymentRequestData->getMethod(), $paymentRequestData->getShop());
                $orderNumber = $this->saveOrder($transactionId, $uniqueId, $statusId ? $statusId : 17); //TODO no static id!

                /** @var Order\Order $order */
                $order = $this->getModelManager()->getRepository(Order\Order::class)
                    ->findOneBy(['number' => $orderNumber]);

                $this->paymentRequestService->completeOrder($order, $requestResponse);

                $this->paymentConfirmService->setOrder($order);
                $this->paymentConfirmService->doRequest();

                // Clear Ratepay session after call for authorization
                $this->sessionHelper->cleanUp();

                /*
                 * redirect to success page
                 */
                $this->redirect(
                    [
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sUniqueID' => $uniqueId,
                        'forceSecure' => true
                    ]
                );
            } else {
                $this->doError(!empty($requestResponse->getCustomerMessage()) ? $requestResponse->getCustomerMessage() : $requestResponse->getReasonMessage());
            }
        } catch (Exception $e) {
            $this->doError($e->getMessage());
        }
    }

    /**
     * calcRequest-function for installment
     */
    public function calcRequestAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender(); // TODO is there a better way?

        $params = $this->Request()->getParams();
        if (!isset($params['calculationAmount']) ||
            !isset($params['calculationValue']) ||
            !isset($params['calculationType'])) {
            exit(0);
        }
        $paymentMethodId = (int)$params['paymentMethodId'];
        $paymentMethod = Shopware()->Models()->find(\Shopware\Models\Payment\Payment::class, $paymentMethodId);
        if (PaymentMethods::isInstallment($paymentMethod) === false) {
            exit(0);
        }

        $calcContext = new InstallmentCalculatorContext(
            $this->sessionHelper->getPaymentConfigSearchObject($paymentMethod),
            $params['calculationAmount'],
            $params['calculationType'],
            (float)$params['calculationValue'] > 0 ? $params['calculationValue'] : 1
        );

        try {
            $planResult = $this->installmentService->getInstallmentPlan($calcContext);
            $html = $this->installmentService->getInstallmentPlanTemplate($planResult->getPlanData());

            $data = [
                'success' => true,
                'html' => $html,
                'installment' => [
                    'isDirectDebitAllowed' => $planResult->isPaymentTypeAllowed(PaymentFirstDay::PAY_TYPE_DIRECT_DEBIT),
                    'isBankTransferAllowed' => $planResult->isPaymentTypeAllowed(PaymentFirstDay::PAY_TYPE_BANK_TRANSFER),
                ],
                'defaults' => [
                    'paymentType' => $planResult->getDefaultPaymentType()
                ]
            ];
        } catch (RequestException $requestException) {
            $data = [
                'success' => false,
                'message' => $this->snippetManager->getNamespace('frontend/ratepay/messages')->get('UnknownError'),
            ];
        }

        // cause shopware does not support json response, we will send it via a simple echo.
        echo json_encode($data);
    }

    public function getWhitelistedCSRFActions()
    {
        return [
            'index',
            'calcRequest'
        ];
    }

    private function doError($message)
    {
        $this->messageManager->addErrorMessage($message);
        $this->redirect(
            [
                'controller' => 'checkout',
                'action' => 'confirm'
            ]
        );
    }

}
