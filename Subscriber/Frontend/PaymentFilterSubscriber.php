<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Front;
use Enlight_Event_EventArgs;
use Monolog\Logger;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\PaymentMethodsService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware_Components_Config;
use Shopware_Components_Modules;

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
    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var PaymentMethodsService
     */
    private $paymentMethodsService;
    /**
     * @var Enlight_Controller_Front
     */
    private $front;

    public function __construct(
        ModelManager $modelManager,
        ContextService $contextService,
        Enlight_Components_Session_Namespace $session,
        Shopware_Components_Config $config,
        Shopware_Components_Modules $modules,
        Enlight_Controller_Front $front,
        ProfileConfigService $profileConfig,
        SessionHelper $sessionHelper,
        PaymentMethodsService $paymentMethodsService,
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
        $this->sessionHelper = $sessionHelper;
        $this->paymentMethodsService = $paymentMethodsService;
        $this->front = $front;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'filterPayments',
        ];
    }

    /**
     * Filters the shown Payments
     * Ratepay-payments will be hidden, if one of the following requirement is not given
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
        if ($this->front->Request()->getControllerName() !== 'checkout') {
            // only filter the payments if the customer is in the checkout process
            return;
        }
        $return = $arguments->getReturn();
        $currency = $this->config->get('currency'); // TODO i think this should be fetched from the session not the config ?!

        $customer = $this->sessionHelper->getCustomer();
        $billingAddress = $this->sessionHelper->getBillingAddress();
        $shippingAddress = $this->sessionHelper->getShippingAddress() ? : $billingAddress;
        if ($billingAddress === null) {
            return $return;
        }

        $availableRatePayMethods = [];
        foreach ($return as $idx => $paymentData) {
            $paymentMethodName = $paymentData['name'];

            if (PaymentMethods::exists($paymentMethodName) === false) {
                // this is not a ratepay method. skip it.
                continue;
            }

            if ($this->paymentMethodsService->isPaymentMethodLockedForCustomer($customer, $paymentMethodName)) {
                // the payment method is locked for the customer
                unset($return[$idx]);
                continue;
            }

            $paymentMethodConfiguration = $this->profileConfig->getPaymentConfiguration((new PaymentConfigSearch())
                ->setPaymentMethod($paymentMethodName)
                ->setBackend(false)
                ->setBillingCountry($billingAddress->getCountry()->getIso())
                ->setShippingCountry($shippingAddress->getCountry()->getIso())
                ->setShop($this->context->getShop())
                ->setCurrency($currency)
            );

            if ($paymentMethodConfiguration === null) {
                // there is not profile/payment config for this method
                unset($return[$idx]);
                continue;
            }

            $isB2b = ValidationService::isCompanySet($billingAddress);

            if (!ValidationService::areBillingAndShippingSame($billingAddress, $shippingAddress) &&
                !$paymentMethodConfiguration->isAllowDifferentAddresses()
            ) {
                unset($return[$idx]);
                continue;
            }

            if ($this->modules->Basket()) {
                $totalAmount = floatval($this->modules->Basket()->sGetAmount()['totalAmount']);


                if (!ValidationService::areAmountsValid($isB2b, $paymentMethodConfiguration, $totalAmount)) {
                    unset($return[$idx]);
                    continue;
                }
                $availableRatePayMethods[$paymentMethodName] = true;
            }
        }

        $selectedPaymentMethod = $customer ? $this->modelManager->find(Payment::class, $customer->getPaymentId()) : null;
        if ($selectedPaymentMethod && PaymentMethods::exists($selectedPaymentMethod)) {
            foreach ($return as $payment) {
                if (!$availableRatePayMethods[$payment['name']] &&
                    $payment['name'] === $selectedPaymentMethod->getName()
                ) {
                    // set payment method of customer to the default payment method, cause ratepay is not available for the customer
                    $customer->setPaymentId($this->config->get('paymentdefault'));
                    $this->modelManager->flush($customer);
                    break;
                }
            }
        }

        return $return;
    }
}
