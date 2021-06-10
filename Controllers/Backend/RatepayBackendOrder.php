<?php

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\InstallmentService;
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

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->logger = Shopware()->Container()->get('ratepay.logger');
        $this->config = $this->container->get(ConfigService::class);
        $this->modelManager = $this->container->get('models');
        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->sessionHelper = $this->container->get(SessionHelper::class);
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

            $result = $this->installmentService->getInstallmentCalculator((new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethodName)
                ->setBackend(true)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop($shopId)
                ->setCurrency($currencyId),
                $totalAmount
            );

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

    /**
     * Returns array of ints containing 2 or 28.
     */
    public function getInstallmentPaymentOptionsAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $paymentMethodName = $params['paymentMeansName'];
        $billingAddressId = $params['billingAddressId'];
        $shippingAddressId = $params['billingAddressId'];
        $currencyId = $params['currencyId'];

        try {
            $billingAddress = $this->modelManager->find(Address::class, $billingAddressId);
            $shippingAddress = $this->modelManager->find(Address::class, $shippingAddressId);

            $installmentConfig = $this->profileConfigService->getInstallmentConfig((new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethodName)
                ->setBackend(true)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop($shopId)
                ->setCurrency($currencyId)
            );
            if ($installmentConfig === null) {
                throw new NoProfileFoundException();
            }

            $optionsString = $installmentConfig->getPaymentFirstDay();
            $optionsArray = explode(',', $optionsString);
            $optionsIntArray = array_map([PaymentFirstDay::class, 'getPayTypByFirstPayDay'], $optionsArray);
            $this->view->assign([
                'success' => true,
                'options' => $optionsIntArray
            ]);
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
            return;
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
        $paymentType = $params['paymentType'];
        $calcParamSet = !empty($params['value']) && !empty($params['type']);
        $type = $calcParamSet ? $params['type'] : 'time';

        $paymentConfigSearch = (new PaymentConfigSearch())
            ->setPaymentMethod($paymentMethodName)
            ->setBackend(true)
            ->setBillingCountry($billingAddress->getCountry()->getIso())
            ->setShippingCountry($shippingAddress->getCountry()->getIso())
            ->setShop($shopId)
            ->setCurrency($currencyId);

        //TODO refactor
        $val = null;
        if ($calcParamSet) {
            $val = $params['value'];
        } else if (PaymentMethods::isZeroPercentInstallment($paymentMethodName)) {
            $installmentConfig = $this->profileConfigService->getInstallmentConfig($paymentConfigSearch);
            $allowedMonths = $installmentConfig ? $installmentConfig->getMonthsAllowed() : null;
            $val = $allowedMonths && isset($allowedMonths[0]) ? $allowedMonths[0] : null;
        }

        if ($val === null) {
            $this->view->assign([
                'success' => false,
                'messages' => [$this->getSnippet('InvalidInstallmentValue')]
            ]);
            return;
        }

        try {
            $dto = new InstallmentRequest($totalAmount, $type, $val, $paymentType);

            $plan = $this->installmentService->initInstallmentData($paymentConfigSearch, $dto);
            $this->view->assign([
                'success' => true,
                'plan' => $plan,
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
        $this->sessionHelper->setInstallmentPaymentType($params['paymentType']);
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getSnippet($name)
    {
        $ns = Shopware()->Snippets()->getNamespace('backend/ratepay');
        return $ns->get($name);
    }
}
