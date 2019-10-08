<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Logger\HistoryLogger;
use RpayRatePay\Services\Logger\RequestLogger;
use RuntimeException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\Models\Order\Detail as OrderDetail;
use Shopware\Models\Order\Order;

abstract class AbstractModifyRequest extends AbstractRequest
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var BasketPosition[]
     */
    protected $items = null;

    /**
     * @var BasketArrayBuilder
     */
    protected $basketArrayBuilder;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var HistoryLogger
     */
    protected $historyLogger;
    /**
     * @var PositionHelper
     */
    protected $positionHelper;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger,
        HistoryLogger $historyLogger,
        ModelManager $modelManager,
        PositionHelper $positionHelper
    )
    {
        parent::__construct($db, $configService, $profileConfigService, $requestLogger);
        $this->historyLogger = $historyLogger;
        $this->modelManager = $modelManager;
        $this->positionHelper = $positionHelper;
    }

    protected function getProfileConfig()
    {
        /** @var OrderAttribute $orderAttribute */
        $orderAttribute = $this->_order->getAttribute();
        return $this->profileConfigService->getProfileConfig(
            $this->_order->getBilling()->getCountry()->getIso(),
            $this->_order->getShop()->getId(),
            $orderAttribute->getRatepayBackend() == 1,
            $this->_order->getPayment()->getName() == PaymentMethods::PAYMENT_INSTALLMENT0
        );
    }

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
        $data = parent::getRequestHead($profileConfig);
        if (!empty($orderId)) {
            $data['External']['OrderId'] = $this->_order->getId();
        }

        $data['TransactionId'] = $this->_order->getTransactionId();
        return $data;
    }

    protected function getRequestContent()
    {
        if ($this->items == null) {
            throw new RuntimeException('please set $items with function `setItems()`');
        }
        if ($this->_order == null) {
            throw new RuntimeException('please set $order with function `setOrder()`');
        }

        if ($this->basketArrayBuilder !== null) {
            $basketFactory = $this->basketArrayBuilder;
        } else {
            $basketFactory = new BasketArrayBuilder($this->_order);
            foreach ($this->items as $item) {
                $detail = $this->getOrderDetailByNumber($item->getProductNumber());
                $basketFactory->addItem($detail ? $detail : $item->getProductNumber(), $item->getQuantity());
            }
        }
        $requestContent = [];
        $requestContent['ShoppingBasket'] = $basketFactory->toArray();
        return $requestContent;
    }

    protected function updateArticleStock($productNumber, $count)
    {
        $repository = $this->modelManager->getRepository(Detail::class);
        $article = $repository->findOneBy(['number' => $productNumber]);
        if ($article) {
            // article still exist
            $article->setInStock($article->getInStock() + $count);
            $this->modelManager->persist($article);
            $this->modelManager->flush();
        }
    }

    /**
     * @param Order|int $order
     */
    public final function setOrder($order = null)
    {
        if (is_numeric($order)) {
            $order = $this->modelManager->find(Order::class, $order);
        }
        if ($order == null) {
            throw new RuntimeException('The order should not be null or the order could not found!');
        }
        $this->_order = $order;
    }

    /**
     * key: product number
     * value: quantity
     * @param BasketArrayBuilder|BasketPosition[] $items
     */
    public final function setItems($items)
    {
        if ($items instanceof BasketArrayBuilder) {
            $this->basketArrayBuilder = $items;
            $this->items = $this->basketArrayBuilder->getSimpleItems();
        } else if (is_array($items)) {
            $this->basketArrayBuilder = null;
            $this->items = $items;
        } else {
            throw new RuntimeException('invalid argument');
        }
    }

    /**
     * @param $productNumber
     * @return AbstractPosition
     */
    protected function getOrderPosition($productNumber)
    {
        if ($productNumber === BasketPosition::SHIPPING_NUMBER) {
            return $this->positionHelper->getShippingPositionForOrder($this->_order);
        } else {
            $detail = $this->getOrderDetailByNumber($productNumber);
            return $this->positionHelper->getPositionForDetail($detail);
        }
    }

    /**
     * @param Order $order
     * @param $productNumber
     * @return OrderDetail
     */
    protected function getOrderDetailByNumber($productNumber)
    {
        /** @var OrderDetail $detail */
        foreach ($this->_order->getDetails() as $detail) {
            if ($detail->getArticleNumber() === $productNumber) {
                return $detail;
            }
        }
        return null;
    }
}
