<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Monolog\Logger;
use RatePAY\Model\Response\PaymentRequest as PaymentResponse;
use RatePAY\RequestBuilder;
use RatePAY\Service\Util;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Component\Service\ShopwareUtil;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Helper\TaxHelper;
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
     * @var PositionHelper
     */
    protected $positionHelper;


    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger,
        CustomerArrayFactory $customerArrayFactory,
        PaymentArrayFactory $paymentArrayFactory,
        ModelManager $modelManager,
        Shopware_Components_Modules $moduleManager,
        Logger $logger
    )
    {
        parent::__construct($db, $configService, $profileConfigService, $requestLogger);
        $this->customerArrayFactory = $customerArrayFactory;
        $this->paymentArrayFactory = $paymentArrayFactory;
        $this->modelManager = $modelManager;
        $this->moduleManager = $moduleManager;
        $this->logger = $logger;
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
        if($this->paymentRequestData->getDfpToken()) {
            $data['CustomerDevice']['DeviceToken'] = $this->paymentRequestData->getDfpToken();
        }
        return $data;
    }

    protected function getRequestContent()
    {
        if($this->paymentRequestData == null) {
            throw new \RuntimeException('please set paymentRequestData with function `setPaymentRequestData()`');
        }
        if($this->isBackend == null) {
            throw new \RuntimeException('please set the backend variable to `true` if it is a backend call with function `setIsBackend()`');
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

    protected function processSuccess()
    {
        // TODO: Implement processSuccess() method.
    }

    public function completeOrder(Order $order, RequestBuilder $paymentResponse)
    {
        $this->setOrderTransactionId($order, $paymentResponse);
        $this->initDiscountPosition($order, $paymentResponse);
        $this->initShippingPosition($order, $paymentResponse);
        $this->insertProductPositions($order, $paymentResponse);
        $this->insertOrderAttributes($order, $paymentResponse);
        $this->setPaymentStatus($order, $paymentResponse);
    }
    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function initShippingPosition(Order $order, RequestBuilder $paymentResponse)
    {
        if ($order->getInvoiceShipping() > 0) {
            // Shopware does have a bug - so this will not work properly
            // Issue: https://issues.shopware.com/issues/SW-24119
            $calculatedShippingTaxRate = TaxHelper::taxFromPrices($order->getInvoiceShippingNet(), $order->getInvoiceShipping());
            $shippingTaxRate = $calculatedShippingTaxRate > 0 ? $order->getInvoiceShippingTaxRate() : 0;

            $shippingPosition = new Shipping();
            $shippingPosition->setSOrderId($order->getId());
            $shippingPosition->setTaxRate($shippingTaxRate);
            $this->modelManager->persist($shippingPosition);

            $this->modelManager->flush($shippingPosition);
        }
    }
    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function initDiscountPosition(Order $order, RequestBuilder $paymentResponse)
    {
        if ($this->configService->isCommitDiscountAsCartItem() == false) {
            /** @var Detail $detail */
            foreach ($order->getDetails() as $detail) {
                if (
                    $detail->getMode() != 0 && // no products
                    ($detail->getMode() != 4 || $detail->getPrice() < 0) // no positive surcharges
                ) {
                    $taxRate = $order->getNet() == 1 && $order->getTaxFree() == 1 ? 0 : $detail->getTaxRate();
                    $discountPosition = new Discount();
                    $discountPosition->setOrderDetail($detail);
                    $discountPosition->setTaxRate($taxRate); //TODO remove!

                    $this->modelManager->persist($discountPosition);
                    $this->modelManager->flush($discountPosition);
                }
            }
        }
    }
    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function insertProductPositions(Order $order, RequestBuilder $paymentResponse)
    {
        $isCommitDiscountAsCartItem = $this->configService->isCommitDiscountAsCartItem();

        $detailPositions = [];
        /** @var Detail $detail */
        foreach ($order->getDetails() as $detail) {

            if ($detail->getMode() != 0 && // not a product
                ($detail->getMode() != 4 || $detail->getPrice() < 0) && // not a positive surcharge
                $isCommitDiscountAsCartItem == false
            ) {
                continue; //this position will be written into the `rpay_ratepay_order_discount` table
            }

            // Shopware does have a bug - so the tax_rate might be the wrong value.
            // Issue: https://issues.shopware.com/issues/SW-24119
            $taxRate = $order->getNet() == 1 && $order->getTaxFree() == 1 ? 0 : $detail->getTaxRate();
            $detailPosition = new Product();
            $detailPosition->setOrderDetail($detail);
            $detailPosition->setTaxRate($taxRate);

            $this->modelManager->persist($detailPosition);
            $detailPositions[] = $detailPosition;
        }
        $this->modelManager->flush($detailPositions);

    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     */
    protected function insertOrderAttributes(Order $order, RequestBuilder $paymentResponse)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy

        $repo = $this->modelManager->getRepository(OrderAttribute::class);
        $orderAttribute = $repo->findOneBy(['orderId' => $order->getId()]);

        $orderAttribute->setAttribute5($paymentResponse->getDescriptor()); // TODO attribute name
        $orderAttribute->setAttribute6($paymentResponse->getTransactionId()); // TODO attribute name
        $orderAttribute->setRatepayBackend($this->isBackend);
        $orderAttribute->setRatepayFallbackDiscount($this->configService->isCommitDiscountAsCartItem());
        $orderAttribute->setRatepayFallbackShipping($this->configService->isCommitDiscountAsCartItem());

        $this->modelManager->flush($orderAttribute);
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
    protected function setPaymentStatus(Order $order, RequestBuilder $paymentResponse)
    {
        //set cleared date
        $order->setClearedDate(new \DateTime());
        $this->modelManager->flush($order);

        $paymentStatusId = $this->configService->getPaymentStatusAfterPayment($order->getPayment());
        if ($paymentStatusId == null) {
            $paymentStatusId = Status::PAYMENT_STATE_OPEN;
            $this->logger->error(
                'Unable to define status for unknown method: ' . $order->getPayment()->getName()
            );
        }

        $this->moduleManager->Order()->setPaymentStatus($order->getId(), $paymentStatusId, false);
    }
}
