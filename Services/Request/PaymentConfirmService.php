<?php


namespace RpayRatePay\Services\Request;


use Monolog\Logger;
use RatePAY\Model\Response\PaymentRequest as PaymentResponse;
use RatePAY\RequestBuilder;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Models\OrderDiscount;
use RpayRatePay\Models\OrderPositions;
use RpayRatePay\Models\OrderShipping;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Components_Modules;

class PaymentConfirmService
{
    /**
     * @var ConfigService
     */
    protected $pluginConfig;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Shopware_Components_Modules
     */
    protected $moduleManager;

    public function __construct(
        ModelManager $modelManager,
        Shopware_Components_Modules $moduleManager,
        ConfigService $pluginConfig,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->moduleManager = $moduleManager;
        $this->pluginConfig = $pluginConfig;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     * @param bool $isBackend
     */
    public function sendPaymentConfirm(Order $order, RequestBuilder $paymentResponse, $isBackend = false)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy

        $this->setOrderTransactionId($order, $paymentResponse);
        $this->initDiscountPosition($order);
        $this->initShippingPosition($order);
        $this->insertProductPositions($order);
        $this->insertOrderAttributes($order, $paymentResponse, $isBackend);
        $this->setPaymentStatus($order);

        $modelFactory = new ModelFactory(null, $isBackend, $order->getNet()); //TODO service
        $modelFactory->setTransactionId($paymentResponse->getTransactionId());
        $modelFactory->setOrderId($order->getNumber());
        $modelFactory->callPaymentConfirm($order->getBilling()->getCountry()->getIso());
    }


    /**
     * @param Order $order
     */
    protected function initShippingPosition(Order $order)
    {
        if ($order->getInvoiceShipping() > 0) {
            // Shopware does have a bug - so this will not work properly
            // Issue: https://issues.shopware.com/issues/SW-24119
            $calculatedShippingTaxRate = TaxHelper::taxFromPrices($order->getInvoiceShippingNet(), $order->getInvoiceShipping());
            $shippingTaxRate = $calculatedShippingTaxRate > 0 ? $order->getInvoiceShippingTaxRate() : 0;

            $shippingPosition = new OrderShipping();
            $shippingPosition->setSOrderId($order->getId());
            $shippingPosition->setTaxRate($shippingTaxRate);
            $this->modelManager->persist($shippingPosition);

            $this->modelManager->flush($shippingPosition);
        }
    }

    /**
     * @param Order $order
     */
    protected function initDiscountPosition(Order $order)
    {
        if ($this->pluginConfig->isCommitDiscountAsCartItem() == false) {
            /** @var Detail $detail */
            foreach ($order->getDetails() as $detail) {
                if (
                    $detail->getMode() != 0 && // no products
                    ($detail->getMode() != 4 || $detail->getPrice() < 0) // no positive surcharges
                ) {
                    $taxRate = $order->getNet() == 1 && $order->getTaxFree() == 1 ? 0 : $detail->getTaxRate();
                    $discountPosition = new OrderDiscount();
                    $discountPosition->setSOrderId($order->getId());
                    $discountPosition->setSOrderDetailId($detail->getId());
                    $discountPosition->setTaxRate($taxRate);

                    $this->modelManager->persist($discountPosition);
                    $this->modelManager->flush($discountPosition);
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    protected function insertProductPositions(Order $order)
    {
        $isCommitDiscountAsCartItem = $this->pluginConfig->isCommitDiscountAsCartItem();

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
            $detailPosition = new OrderPositions();
            $detailPosition->setSOrderDetailId($detail->getId());
            $detailPosition->setTaxRate($taxRate);

            $this->modelManager->persist($detailPosition);
            $detailPositions[] = $detailPosition;
        }
        $this->modelManager->flush($detailPositions);

    }

    /**
     * @param Order $order
     * @param RequestBuilder $paymentResponse
     * @param bool $backend
     */
    protected function insertOrderAttributes(Order $order, RequestBuilder $paymentResponse, $backend = false)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy

        $repo = $this->modelManager->getRepository(OrderAttribute::class);
        $orderAttribute = $repo->findOneBy(['orderId' => $order->getId()]);

        $orderAttribute->setAttribute5($paymentResponse->getDescriptor()); // TODO attribute name
        $orderAttribute->setAttribute6($paymentResponse->getTransactionId()); // TODO attribute name
        $orderAttribute->setRatepayBackend($backend);
        $orderAttribute->setRatepayFallbackDiscount($this->pluginConfig->isCommitDiscountAsCartItem());
        $orderAttribute->setRatepayFallbackShipping($this->pluginConfig->isCommitDiscountAsCartItem());

        $this->modelManager->flush($orderAttribute);
    }

    protected function setOrderTransactionId(Order $order, RequestBuilder $paymentResponse)
    {
        /** @var $paymentResponse PaymentResponse */ // RequestBuilder is a proxy

        $order->setTransactionId($paymentResponse->getTransactionId());
        $this->modelManager->flush($order);
    }

    /**
     * @param Order $order
     */
    protected function setPaymentStatus($order)
    {
        //set cleared date
        $order->setClearedDate(new \DateTime());
        $this->modelManager->flush($order);

        $paymentStatusId = $this->pluginConfig->getPaymentStatusAfterPayment($order->getPayment());
        if ($paymentStatusId == null) {
            $paymentStatusId = Status::PAYMENT_STATE_OPEN;
            $this->logger->error(
                'Unable to define status for unknown method: ' . $order->getPayment()->getName()
            );
        }

        $this->moduleManager->Order()->setPaymentStatus($order->getId(), $paymentStatusId, false);
    }

}
