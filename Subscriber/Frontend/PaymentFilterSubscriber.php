<?php

namespace RpayRatePay\Subscriber\Frontend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Monolog\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware_Components_Config;
use Shopware_Components_Modules;
use Shopware_Plugins_Frontend_RpayRatePay_Component_Validation;

class PaymentFilterSubscriber implements SubscriberInterface
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
     * @var ProfileConfigService
     */
    protected $profileConfig;
    /**
     * @var Shopware_Components_Config
     */
    protected $config;
    /**
     * @var Shopware_Components_Modules
     */
    protected $modules;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ShopContextInterface|null
     */
    protected $context;

    public function __construct(
        ModelManager $modelManager,
        ContextService $contextService,
        Enlight_Components_Session_Namespace $session,
        Shopware_Components_Config $config,
        Shopware_Components_Modules $modules,
        ProfileConfigService $profileConfig,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->session = $session;
        $this->context = $contextService->getContext();
        $this->config = $config;
        $this->modules = $modules;
        $this->profileConfig = $profileConfig;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'filterPayments',
        ];
    }

    /**
     * Filters the shown Payments
     * RatePAY-payments will be hidden, if one of the following requirement is not given
     *  - Delivery Address is not allowed to be not the same as billing address
     *  - The Customer must be over 18 years old
     *  - The Country must be germany or austria
     *  - The Currency must be EUR
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return array|void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function filterPayments(Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();
        $currency = $this->config->get('currency');
        $userId = $this->session->get('sUserId');
        if (empty($userId) || empty($currency)) {
            return;
        }

        /** @var Customer $customer */
        $customer = $this->modelManager->find(Customer::class, $userId);
        $wrappedUser = new ShopwareCustomerWrapper($customer, $this->modelManager); //TODO service ?

        $countryBilling = $wrappedUser->getBillingCountry();
        $countryDelivery = $wrappedUser->getShippingCountry();

        if (is_null($countryDelivery)) {
            $countryDelivery = $countryBilling;
        }

        $config = $this->getRatePayPluginConfigByCountry($this->context->getShop()->getId(), $countryBilling, false);
        foreach ($config as $payment => $data) {
            $show[$payment] = $data['status'] == 2 ? true : false;

            $validation = $this->getValidator($customer);

            $validation->setAllowedCurrencies($data['currency']);
            $validation->setAllowedCountriesBilling($data['country_code_billing']);
            $validation->setAllowedCountriesDelivery($data['country_code_delivery']);

            if ($validation->isRatepayHidden()) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isCurrencyValid($currency)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isBillingCountryValid($countryBilling)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                if (!$data['address']) {
                    $shop[$payment] = false;
                    continue;
                }
            }

            if ($this->modules->Basket()) {
                $basket = $this->modules->Basket()->sGetAmount();
                $basket = $basket['totalAmount']; //is this always brutto?

                $this->logger->info('BasketAmount: ' . $basket);
                $isB2b = $validation->isCompanyNameSet();

                if (!ValidationService::areAmountsValid($isB2b, $data, $basket)) {
                    $show[$payment] = false;
                    continue;
                }
            }
        }

        $paymentModel = $this->modelManager->find(Payment::class, $customer->getPaymentId());
        $setToDefaultPayment = false;

        $payments = [];
        foreach ($return as $payment) {
            if ($payment['name'] === PaymentMethods::PAYMENT_INVOICE && !$show['invoice']) {
                $this->logger->info('RatePAY: Filter RatePAY-Invoice');
                $setToDefaultPayment = $paymentModel->getName() === $payment['name'] ?: $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === PaymentMethods::PAYMENT_DEBIT && !$show['debit']) {
                $this->logger->info('RatePAY: Filter RatePAY-Debit');
                $setToDefaultPayment = $paymentModel->getName() === $payment['name'] ?: $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === PaymentMethods::PAYMENT_RATE && !$show['installment']) {
                $this->logger->info('RatePAY: Filter RatePAY-Rate');
                $setToDefaultPayment = $paymentModel->getName() === $payment['name'] ?: $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === PaymentMethods::PAYMENT_INSTALLMENT0 && !$show['installment0']) {
                $this->logger->info('RatePAY: Filter RatePAY-Rate0');
                $setToDefaultPayment = $paymentModel->getName() === $payment['name'] ?: $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === PaymentMethods::PAYMENT_PREPAYMENT && !$show['prepayment']) {
                $this->logger->info('RatePAY: Filter RatePAY-Prepayment');
                $setToDefaultPayment = $paymentModel->getName() === $payment['name'] ?: $setToDefaultPayment;
                continue;
            }
            $payments[] = $payment;
        }

        if ($setToDefaultPayment) {
            $customer->setPaymentId($this->config->get('paymentdefault'));
            $this->modelManager->flush($customer);
        }

        return $payments;
    }

    private function getValidator($user)
    {
        return new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($user); //TODO service
    }

    /**
     * Get ratepay plugin config from rpay_ratepay_config table
     *
     * @param $shopId
     * @param $country
     * @param bool $backend
     * @return array
     */
    private function getRatePayPluginConfigByCountry($shopId, Country $country, $backend = false)
    {
        $paymentConfig = [];
        foreach (PaymentMethods::getNames() as $payment) {
            $result = $this->profileConfig->getConfigForPayment($shopId, $country->getIso(), $payment, $backend);

            if (!empty($result)) {
                $paymentConfig[$payment] = $result;
            }
        }

        return $paymentConfig;
    }
}
