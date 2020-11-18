<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


use DateTime;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Monolog\Logger;
use RatePAY\Model\Response\AbstractResponse;
use RatePAY\Model\Response\PaymentRequest as PaymentResponse;
use RatePAY\RequestBuilder;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\Position\Discount;
use RpayRatePay\Models\Position\Product;
use RpayRatePay\Models\Position\Shipping;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Factory\BasketArrayFactory;
use RpayRatePay\Services\Factory\CustomerArrayFactory;
use RpayRatePay\Services\Factory\PaymentArrayFactory;
use RpayRatePay\Services\Logger\RequestLogger;
use RpayRatePay\Services\PaymentMethodsService;
use RuntimeException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Components_Modules;

class PaymentRequestService extends AbstractRequest
{

    /**
     * @var CustomerArrayFactory
     */
    protected $customerArrayFactory;
    /**
     * @var PaymentArrayFactory
     */
    protected $paymentArrayFactory;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var Shopware_Components_Modules
     */
    protected $moduleManager;
    /**
     * @var Logger
     */
    protected $logger;


    /**
     * @var PaymentRequestData
     */
    protected $paymentRequestData;

    /**
     * @var boolean
     */
    protected $isBackend;
    /**
     * @var ProfileConfigService
     */
    private $profileConfigService;
    /**
     * @var PaymentMethodsService
     */
    private $paymentMethodsService;


    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        RequestLogger $requestLogger,
        ProfileConfigService $profileConfigService,
        CustomerArrayFactory $customerArrayFactory,
        PaymentArrayFactory $paymentArrayFactory,
        ModelManager $modelManager,
        Shopware_Components_Modules $moduleManager,
        PaymentMethodsService $paymentMethodsService,
        Logger $logger
    )
    {
        parent::__construct($db, $configService, $requestLogger);
        $this->customerArrayFactory = $customerArrayFactory;
        $this->paymentArrayFactory = $paymentArrayFactory;
        $this->modelManager = $modelManager;
        $this->moduleManager = $moduleManager;
        $this->logger = $logger;
        $this->profileConfigService = $profileConfigService;
        $this->paymentMethodsService = $paymentMethodsService;
    }

    /**
     * @param bool $isBackend
     */
    public function setIsBackend($isBackend)
    {
        $this->isBackend = $isBackend;
    }

    /**
     * @param PaymentRequestData $paymentRequestData
     */
    public function setPaymentRequestData($paymentRequestData)
    {
        $this->paymentRequestData = $paymentRequestData;
    }

    public function completeOrder(Order $order, RequestBuilder $paymentResponse)
    {
        $this->setOrderTransactionId($order, $paymentResponse);
        $this->initShippingPosition($order, $paymentResponse);
        $this->insertProductPositions($order, $paymentResponse);
        $this->insertOrderAttributes($order, $paymentResponse);
        $this->setPaymentStatus($order, $paymentResponse);
    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function setOrderTransactionId(Order $order, RequestBuilder $paymentResponse)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy
        $order->setTransactionId($paymentResponse->getTransactionId());
        $this->modelManager->flush($order);
    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function initShippingPosition(Order $order, RequestBuilder $paymentResponse)
    {
        if ($order->getInvoiceShipping() > 0) {
            $shippingPosition = new Shipping();
            $shippingPosition->setSOrderId($order->getId());
            $this->modelManager->persist($shippingPosition);
            $this->modelManager->flush($shippingPosition);
        }
    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function insertProductPositions(Order $order, RequestBuilder $paymentResponse)
    {
        $entitiesToFlush = [];
        /** @var Detail $detail */
        foreach ($order->getDetails() as $detail) {
            if (PositionHelper::isDiscount($detail)) {
                $position = new Discount();
                $position->setOrderDetail($detail);
            } else {
                $position = new Product();
                $position->setOrderDetail($detail);
            }
            $this->modelManager->persist($position);
            $entitiesToFlush[] = $position;
        }
        $this->modelManager->flush($entitiesToFlush);

    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function insertOrderAttributes(Order $order, RequestBuilder $paymentResponse)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy
        $orderAttribute = $order->getAttribute();
        if ($orderAttribute === null) {
            $orderAttribute = new OrderAttribute();
            $orderAttribute->setOrder($order);
            $this->modelManager->persist($orderAttribute);
            $order->setAttribute($orderAttribute);
        }

        $orderAttribute->setRatepayDescriptor($paymentResponse->getDescriptor());
        $orderAttribute->setRatepayBackend($this->isBackend);
        $orderAttribute->setRatepayFallbackDiscount($this->configService->isCommitDiscountAsCartItem());
        $orderAttribute->setRatepayFallbackShipping($this->configService->isCommitShippingAsCartItem());

        /** @deprecated v6.2 */
        $orderAttribute->setRatepayDirectDelivery(
            PaymentMethods::isInstallment($order->getPayment()) === false ||
            $this->configService->isInstallmentDirectDelivery()
        );

        $this->modelManager->flush($orderAttribute);
    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function setPaymentStatus(Order $order, RequestBuilder $paymentResponse)
    {
        //set cleared date
        $order->setClearedDate(new DateTime());
        $this->modelManager->flush($order);

        $paymentStatusId = $this->configService->getPaymentStatusAfterPayment($order->getPayment(), $order->getShop());
        if ($paymentStatusId == null) {
            $paymentStatusId = Status::PAYMENT_STATE_OPEN;
            $this->logger->error(
                'Unable to define status for unknown method: ' . $order->getPayment()->getName()
            );
        }

        $this->moduleManager->Order()->setPaymentStatus($order->getId(), $paymentStatusId, false);
    }

    protected function getCallName()
    {
        return 'paymentRequest';
    }

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
        $data = parent::getRequestHead($profileConfig);
        $data['External'] = [
            'OrderId' => null, //TODO currently not transmitted
            'MerchantConsumerId' => $this->paymentRequestData->getCustomer()->getNumber()
        ];
        if ($this->paymentRequestData->getDfpToken()) {
            $data['CustomerDevice']['DeviceToken'] = $this->paymentRequestData->getDfpToken();
        }
        return $data;
    }

    protected function getRequestContent()
    {
        if ($this->paymentRequestData === null) {
            throw new RuntimeException('please set paymentRequestData with function `setPaymentRequestData()`');
        }
        if ($this->isBackend === null) {
            throw new RuntimeException('please set the backend variable to `true` if it is a backend call. use function `setIsBackend()`');
        }

        $items = $this->paymentRequestData->getItems();
        if(count($items) === 0 || (count($items) === 1 && $items[0] === 'shipping')) {
            throw new RuntimeException('there are no items in the basket. this order can not be proceeded.');
        }

        $basketFactory = new BasketArrayBuilder($this->paymentRequestData);
        $shoppingBasket = $basketFactory->toArray();

        $data = [
            CustomerArrayFactory::ARRAY_KEY => $this->customerArrayFactory->getData($this->paymentRequestData),
            BasketArrayFactory::ARRAY_KEY => $shoppingBasket,
            PaymentArrayFactory::ARRAY_KEY => $this->paymentArrayFactory->getData($this->paymentRequestData)
        ];

        return $data;
    }

    protected function getProfileConfig()
    {
        return $this->profileConfigService->getProfileConfig(
            $this->paymentRequestData->getBillingAddress()->getCountry()->getIso(),
            $this->paymentRequestData->getShop()->getId(),
            $this->isBackend,
            $this->paymentRequestData->getMethod()->getName() == PaymentMethods::PAYMENT_INSTALLMENT0
        );
    }

    protected function processSuccess()
    {
        // TODO: Implement processSuccess() method.
    }

    protected function processFailed(RequestBuilder $response)
    {
        /** @var $response AbstractResponse */
        if (in_array((int)$response->getReasonCode(), [703, 720, 721], true)) {
            $this->paymentMethodsService->lockPaymentMethodForCustomer($this->paymentRequestData->getCustomer(), $this->paymentRequestData->getMethod());
        }
    }
}
