<?php

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\Component\InstallmentCalculator\Service\SessionHelper as InstallmentSessionHelper;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Address;

class Shopware_Controllers_Backend_RatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ConfigService
     */
    protected $config;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;
    /**
     * @var InstallmentService
     */
    protected $installmentService;
    /**
     * @var SessionHelper
     */
    protected $sessionHelper;
    /**
     * @var InstallmentSessionHelper
     */
    private $installmentSessionHelper;

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->logger = Shopware()->Container()->get('ratepay.logger');
        $this->config = $this->container->get(ConfigService::class);
        $this->modelManager = $this->container->get('models');
        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->sessionHelper = $this->container->get(SessionHelper::class);
        $this->installmentSessionHelper = $this->container->get(InstallmentSessionHelper::class);
    }

    /**
     * Write to session. We must because there is no way to send extra data with order create request.
     */
    public function setBankDataAction()
    {
        $params = $this->Request()->getParams();

        $accountNumber = trim($params['iban']);
        $customerId = intval($params['customerId']);

        if (ValidationLib::isIbanValid($params['iban'])) {
            $this->sessionHelper->setBankData($customerId, null, $accountNumber);
            $this->view->assign([
                'success' => true,
            ]);
        } else {
            $this->view->assign([
                'success' => false,
                'messages' => [$this->getSnippet('InvalidIban')]
            ]);
        }


    }

    public function getInstallmentInfoAction()
    {
        try {
            $params = $this->Request()->getParams();

            $shopId = $params['shopId'];
            $paymentMethodName = $params['paymentMeansName'];
            $billingAddressId = $params['billingAddressId'];
            $shippingAddressId = $params['billingAddressId'];
            $currencyId = $params['currencyId'];
            $totalAmount = $params['totalAmount'];

            $billingAddress = $this->modelManager->find(Address::class, $billingAddressId);
            $shippingAddress = $this->modelManager->find(Address::class, $shippingAddressId);

            $paymentConfigSearch = (new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethodName)
                ->setBackend(true)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop($shopId)
                ->setCurrency($currencyId);

            $context = new InstallmentCalculatorContext($paymentConfigSearch, $totalAmount);
            $result = $this->installmentService->getInstallmentCalculator($context);

            $this->view->assign([
                'success' => true,
                'termInfo' => $result
            ]);
        } catch (Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => ['An error occurred while loading the calculator (Exception: ' . $e->getMessage() . ')']
            ]);
        }
    }

    public function getInstallmentPlanAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingAddressId = $params['billingAddressId'];
        $shippingAddressId = $params['billingAddressId'];
        $currencyId = $params['currencyId'];

        $billingAddress = $this->modelManager->find(Address::class, $billingAddressId);
        $shippingAddress = $this->modelManager->find(Address::class, $shippingAddressId);

        $paymentMethodName = $params['paymentMeansName'];
        $totalAmount = $params['totalAmount'];
        $calcParamSet = !empty($params['value']) && !empty($params['type']);

        if (!$calcParamSet) {
            $this->view->assign([
                'success' => false,
                'messages' => [$this->getSnippet('InvalidInstallmentValue')]
            ]);

            return;
        }

        $calculationType = $params['type'];
        $calculationValue = $params['value'];

        $paymentConfigSearch = (new PaymentConfigSearch())
            ->setPaymentMethod($paymentMethodName)
            ->setBackend(true)
            ->setBillingCountry($billingAddress->getCountry()->getIso())
            ->setShippingCountry($shippingAddress->getCountry()->getIso())
            ->setShop($shopId)
            ->setCurrency($currencyId);

        $installmentContext = new InstallmentCalculatorContext(
            $paymentConfigSearch,
            $totalAmount,
            $calculationType,
            $calculationValue
        );

        try {
            $planResult = $this->installmentService->initInstallmentData($installmentContext);

            $this->view->assign([
                'success' => true,
                'plan' => $planResult->getPlanData(),
                'paymentTypes' => $planResult->getAllowedPaymentTypes(),
                'defaults' => [
                    'paymentType' => $planResult->getDefaultPaymentType()
                ],
            ]);
        } catch (Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
        }
    }

    public function updatePaymentSubtypeAction()
    {
        $params = $this->Request()->getParams();
        if (!in_array($params['paymentType'], PaymentFirstDay::PAY_TYPES, true)) {
            $this->view->assign([
                'success' => false,
                'messages' => [$this->getSnippet('InvalidPaymentType')]
            ]);

            return;
        }

        $this->installmentSessionHelper->setPaymentType($params['paymentType']);

        $this->view->assign([
            'success' => true,
            'messages' => [$this->getSnippet('paymentTypeUpdated')]
        ]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getSnippet($name)
    {
        return Shopware()->Snippets()->getNamespace('backend/ratepay')->get($name);
    }
}
