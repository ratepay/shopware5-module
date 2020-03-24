<?php

namespace RpayRatePay\Component\Mapper;

use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Services\Config\ConfigService;
use RuntimeException;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Currency;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class BasketArrayBuilder
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var array
     */
    protected $basket;

    /**
     * @var bool
     */
    protected $useFallbackShipping;

    /** @var bool */
    protected $useFallbackDiscount;
    /**
     * @var PaymentRequestData|Order
     */
    protected $paymentRequestData;
    /**
     * contains a simple list of items
     * key: product number
     * value: quantity
     * @var
     */
    protected $simpleItems = [];
    /**
     * @var Order
     */
    private $order;
    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * BasketArrayBuilder constructor.
     * @param Order|PaymentRequestData $data
     * @param iterable $items
     * @param null $requestType
     */
    public function __construct($data, $items = null)
    {
        $configService = Shopware()->Container()->get(ConfigService::class); //TODO
        $this->modelManager = Shopware()->Container()->get('models'); //TODO
        $this->eventManager = Shopware()->Container()->get('events'); //TODO

        $this->basket = [];

        if ($data instanceof Order) {
            $this->order = $data;
            $this->basket['Currency'] = $this->order->getCurrency();
            $this->useFallbackShipping = $this->order->getAttribute()->getRatepayFallbackShipping() == 1;
            $this->useFallbackDiscount = $this->order->getAttribute()->getRatepayFallbackDiscount() == 1;
        } else if ($data instanceof PaymentRequestData) {
            $this->paymentRequestData = $data;
            $this->basket['Currency'] = $this->modelManager->find(Currency::class, $this->paymentRequestData->getCurrencyId())->getCurrency();
            $this->useFallbackShipping = $configService->isCommitShippingAsCartItem();
            $this->useFallbackDiscount = $configService->isCommitDiscountAsCartItem();
            if ($items == null) {
                $items = $data->getItems();
            }
        } else {
            throw new RuntimeException('$data must by type of ' . Order::class . ' or ' . PaymentRequestData::class . '. ' . ($data ? get_class($data) : 'null') . ' given');
        }

        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * if no quantity is given, the quantity will taken from the detail object - if it is a Detail object.
     * if you provide `shipping` as $item, it will automatically add a shipping position
     *
     * @param string|Detail|PositionStruct $item
     * @param int|null $quantity
     */
    public function addItem($item, $quantity = null)
    {
        $orderDetail = null;

        if ($item !== 'shipping') {
            if ($item instanceof Detail === false || $item->getId() !== null) {
                // in the following lines we will call THIS function recursively. Maybe the `$item` is just a DTO
                // if so, we will skip cause the filter event has been already triggered
                $item = $this->eventManager->filter('RatePAY_filter_order_items', $item, ['quantity' => $quantity]);
            }
        }
        if ($item === 'shipping') {
            $this->addShippingItem();
            return;
        } else if ($item instanceof Detail) {
            if (PositionHelper::isDiscount($item) && $this->useFallbackDiscount == false) {
                $this->addDiscountItem($item);
                return;
            }
            $name = $item->getArticleName();
            $productNumber = $item->getArticleNumber();
            $itemQuantity = $quantity ?: $item->getQuantity();
            $orderDetail = $item->getId() ? $item : null;
        } else if ($item instanceof PositionStruct) {
            if (PositionHelper::isDiscount($item) && $this->useFallbackDiscount == false) {
                $this->addDiscountItem($item);
                return;
            }
            $name = $item->getName();
            $productNumber = $item->getNumber();
            $itemQuantity = $quantity ?: $item->getQuantity();

        } else if (is_array($item)) {
            //frontend call
            $detail = new Detail();
            $detail->setArticleNumber($item['ordernumber']);
            $detail->setNumber($item['ordernumber']);
            $detail->setArticleName($item['articlename']);
            $detail->setQuantity(intval($item['quantity']));
            $detail->setPrice(floatval($item['priceNumeric']));
            $detail->setTaxRate(floatval($item['tax_rate']));
            $detail->setMode(intval($item['modus']));
            $this->addItem($detail);
            return;
        } else {
            throw new RuntimeException('type ' . get_class($item) . ' is not supported');
        }

        $price = TaxHelper::getItemGrossPrice($this->order ?: $this->paymentRequestData, $item);
        $taxRate = TaxHelper::getItemTaxRate($this->order ?: $this->paymentRequestData, $item);

        $this->basket['Items'][] = [
            'Item' => [
                'Description' => $name,
                'ArticleNumber' => $productNumber,
                'Quantity' => $itemQuantity,
                'UnitPriceGross' => $price,
                'TaxRate' => $taxRate,
            ]
        ];
        $position = new BasketPosition($productNumber, $itemQuantity);
        if ($orderDetail) {
            $position->setOrderDetail($orderDetail);
        }
        $this->simpleItems[$position->getProductNumber()] = $position;
    }

    public function addShippingItem()
    {
        if ($this->order) {
            $shippingCost = TaxHelper::getShippingGrossPrice($this->order);
            $shippingTax = TaxHelper::getShippingTaxRate($this->order);
        } else if ($this->paymentRequestData) {
            $shippingCost = TaxHelper::getShippingGrossPrice($this->paymentRequestData);
            $shippingTax = TaxHelper::getShippingTaxRate($this->paymentRequestData);
        } else {
            throw new RuntimeException('no payment request data or order is available');
        }
        if ($shippingCost <= 0) {
            return;
        }

        if ($this->useFallbackShipping) {
            $detail = new Detail();
            $detail->setNumber(BasketPosition::SHIPPING_NUMBER);
            $detail->setArticleNumber(BasketPosition::SHIPPING_NUMBER);
            $detail->setQuantity(1);
            $detail->setArticleName('shipping');
            $detail->setPrice($shippingCost);
            $detail->setTaxRate($shippingTax);
            $detail->setMode(PositionHelper::MODE_RP_SHIPPING);
            $this->addItem($detail);
        } else {
            $this->basket['Shipping'] = [
                'Description' => 'Shipping costs',
                'UnitPriceGross' => $shippingCost,
                'TaxRate' => $shippingTax,
            ];
            $position = new BasketPosition(BasketPosition::SHIPPING_NUMBER, 1);
            $this->simpleItems[$position->getProductNumber()] = $position;
        }
    }

    /**
     * @param PositionStruct|Detail $item
     */
    public function addDiscountItem($item)
    {
        if (PositionHelper::isDiscount($item) === false) {
            throw new RuntimeException('given object is not a discount (number: ' . $item->getNumber() . ')');
        }
        if ($this->useFallbackDiscount) {
            $this->addItem($item);
            return;
        } else {
            if ($item instanceof Detail) {
                $name = $item->getArticleName();
                $productNumber = $item->getArticleNumber();
                $price = TaxHelper::getItemGrossPrice($item->getOrder(), $item);
                $taxRate = TaxHelper::getItemTaxRate($item->getOrder(), $item);
            } else if ($item instanceof PositionStruct) {
                $name = $item->getName();
                $productNumber = $item->getNumber();
                $price = TaxHelper::getItemGrossPrice($this->paymentRequestData, $item);
                $taxRate = TaxHelper::getItemTaxRate($this->paymentRequestData, $item);
            } else {
                // should never occurs cause the function call `PositionHelper::isDiscount`
                // already throw an exception if it is the wrong type
                throw new RuntimeException('the object must be a type of ' . Detail::class . ' or ' . PositionStruct::class);
            }

            if (isset($this->basket['Discount'])) {
                throw new RuntimeException('ratepay does not support more than one discount element');
            } else {
                $this->basket['Discount'] = [
                    'Description' => $name,
                    'UnitPriceGross' => $price,
                    'TaxRate' => $taxRate,
                ];
            }
            $position = new BasketPosition($productNumber, 1);
            if ($item instanceof Detail) {
                $position->setOrderDetail($item);
            }
            $this->simpleItems[$productNumber] = $position;
        }
    }

    public function toArray()
    {
        return $this->basket;
    }

    /**
     * does return all items in the basket as a simple array
     * @return BasketPosition[]
     */
    public function getSimpleItems()
    {
        return $this->simpleItems;
    }
}
