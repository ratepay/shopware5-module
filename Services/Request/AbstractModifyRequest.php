<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Logger\HistoryLogger;
use RpayRatePay\Services\Logger\RequestLogger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\Models\Order\Order;

abstract class AbstractModifyRequest extends AbstractRequest
{
    /**
     * @var Order
     */
    protected $_order;

    protected $items = null;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var HistoryLogger
     */
    protected $historyLogger;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger,
        HistoryLogger $historyLogger,
        ModelManager $modelManager
    )
    {
        parent::__construct($db, $configService, $profileConfigService, $requestLogger);
        $this->historyLogger = $historyLogger;
        $this->modelManager = $modelManager;
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
        if($this->_order == null) {
            throw new \RuntimeException('please set order with function `setOrder()`');
        }
        $basketFactory = new BasketArrayBuilder($this->_order, $this->items, $this->_subType);
        $requestContent = [];
        $requestContent['ShoppingBasket'] = $basketFactory->toArray();
        return $requestContent;
    }

    protected function updatePosition($orderID, $articleordernumber, $bind)
    {
        //TODO refactor
        if ($articleordernumber === 'shipping') {
            Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
        } else if ($articleordernumber === 'discount') {
            Shopware()->Db()->update('rpay_ratepay_order_discount', $bind, '`s_order_id`=' . $orderID); //update all discounts
        } else {
            $positionId = Shopware()->Db()->fetchOne('SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?', [$orderID, $articleordernumber]);
            Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $positionId);
        }
    }


    protected function updateArticleStock($productNumber, $count)
    {
        $repository = $this->modelManager->getRepository(Detail::class);
        $article = $repository->findOneBy(['number' => $productNumber]);
        if($article) {
            // article still exist
            $article->setInStock($article->getInStock() + $count);
            $this->modelManager->persist($article);
            $this->modelManager->flush();
        }
    }

    /**
     * @param Order|int $order
     */
    public final function setOrder($order = null) {
        if(is_numeric($order)) {
            $order = $this->modelManager->find(Order::class, $order);
        }
        if($order == null) {
            throw new \RuntimeException('The order should not be null or the order could not found!');
        }
        $this->_order = $order;
    }

    public final function setItems($items = null) {
        $this->items = $items;
    }
}
