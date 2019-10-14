<?php

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * RpayRatepay
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Monolog\Logger;
use RatePAY\Model\Response\PaymentRequest;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Component\Service\ShopwareUtil;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Enum\PaymentSubType;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\Factory\PaymentRequestDataFactory;
use RpayRatePay\Services\InstallmentService;
use RpayRatePay\Services\Logger\RequestLogger;
use RpayRatePay\Services\PaymentProcessorService;
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
     * @var object|ConfigService
     */
    private $configService;
    /**
     * @var PaymentConfirmService
     */
    private $paymentConfirmService;
    /**
     * @var SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var object|ProfileConfigService
     */
    private $profileConfigService;
    /**
     * @var object|InstallmentService
     */
    protected $installmentService;


    public function setContainer(Container $container = null)
    {
        parent::setContainer($container);

        $this->paymentRequestDataFactory = $this->container->get(PaymentRequestDataFactory::class);
        $this->paymentRequestService = $this->container->get(PaymentRequestService::class);
        $this->paymentConfirmService = $this->container->get(PaymentConfirmService::class);
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->logger = $container->get('rpay_rate_pay.logger');
        $this->dfpService = $this->container->get(DfpService::class);
        $this->configService = $this->container->get(ConfigService::class);
        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->sessionHelper = $this->container->get(SessionHelper::class);
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

        $this->logger->info('Proceed with RatePAY payment');
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

                $statusId = $this->configService->getPaymentStatusAfterPayment($paymentRequestData->getMethod());
                $orderNumber = $this->saveOrder($transactionId, $uniqueId, $statusId ? $statusId : 17);

                $order = Shopware()->Models()->getRepository(Order\Order::class)
                    ->findOneBy(['number' => $orderNumber]);

                $this->paymentRequestService->completeOrder($order, $requestResponse);

                $this->paymentConfirmService->setOrder($order);
                $this->paymentConfirmService->doRequest();

                // Clear RatePAY session after call for authorization
                $this->sessionHelper->cleanUp();
                $this->dfpService->deleteDfpId();

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
                $this->redirect(
                    [
                        'controller' => 'checkout',
                        'action' => 'confirm',
                        'rpay_message' => !empty($requestResponse->getCustomerMessage()) ? $requestResponse->getCustomerMessage() : $requestResponse->getReasonMessage()
                    ]
                );
            }
        } catch(\Exception $e) {
            $this->redirect(
                [
                    'controller' => 'checkout',
                    'action' => 'confirm',
                    'rpay_message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * calcRequest-function for installment
     */
    public function calcRequestAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender(); // TODO is there a better way?

        $params = $this->Request()->getParams();
        if(!isset($params['calculationAmount']) ||
            !isset($params['calculationValue']) ||
            !isset($params['calculationType']) ||
            !isset($params['paymentFirstday'])) {
            exit(0);
        }
        $paymentMethod = $this->sessionHelper->getPaymentMethod();
        $billingAddress = $this->sessionHelper->getBillingAddress();

        $requestDto = new InstallmentRequest(
            $params['calculationAmount'],
            $params['calculationType'],
            $params['calculationValue'],
            null,
            $params['paymentFirstday']
        );

        echo $this->installmentService->getInstallmentPlanTemplate(
            $billingAddress->getCountry()->getIso(),
            Shopware()->Shop()->getId(),
            $paymentMethod,
            false,
            $requestDto
        );
    }

    public function getWhitelistedCSRFActions()
    {
        return [
            'index',
            'calcRequest'
        ];
    }

}
