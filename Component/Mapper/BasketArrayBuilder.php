<?php
namespace RpayRatePay\Component\Mapper;
use RatePAY\Service\Math;
use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\Position\Product as ProductPosition;
use RpayRatePay\Services\Config\ConfigService;
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

    /** @var bool  */
    protected $useFallbackDiscount;

    /**
     * @var Order
     */
    private $order;
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
     * BasketArrayBuilder constructor.
     * @param Order|PaymentRequestData $data
     * @param iterable $items
     * @param null $requestType
     */
    public function __construct($data, $items = null)
    {
        $configService = Shopware()->Container()->get(ConfigService::class); //TODO
        $this->modelManager = Shopware()->Container()->get('models'); //TODO

        $this->basket = [];

        if($data instanceof Order) {
            $this->order = $data;
            $this->basket['Currency'] = $this->order->getCurrency();
            $this->useFallbackShipping = $this->order->getAttribute()->getRatepayFallbackShipping() == 1;
            $this->useFallbackDiscount = $this->order->getAttribute()->getRatepayFallbackDiscount() == 1;
        } else if($data instanceof PaymentRequestData) {
            $this->paymentRequestData = $data;
            $this->basket['Currency'] = $this->modelManager->find(Currency::class,  $this->paymentRequestData->getCurrencyId())->getCurrency();
            $this->useFallbackShipping = $configService->isCommitShippingAsCartItem();
            $this->useFallbackDiscount = $configService->isCommitDiscountAsCartItem();
            if($items == null) {
                $items = $data->getItems();
            }
        } else {
            throw new \RuntimeException('$data must by type of '.Order::class.' or '.PaymentRequestData::class.'. '. ($data ? get_class($data) : 'null') . ' given');
        }

        foreach($items as $item) {
            $this->addItem($item);
        }
    }

    public function toArray()
    {
        return $this->basket;
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
        if($item === 'shipping') {
            $this->addShippingItem();
            return;
        } else if($item instanceof Detail) {
            if(PositionHelper::isDiscount($item) && $this->useFallbackDiscount == false) {
                $this->addDiscountItem($item);
                return;
            }
            $name = $item->getArticleName();
            $productNumber = $item->getArticleNumber();
            $itemQuantity = $quantity ? : $item->getQuantity();
            $price = TaxHelper::getItemGrossPrice($item->getOrder(), $item);
            $taxRate = TaxHelper::getItemTaxRate($item->getOrder(), $item);

        } else if($item instanceof PositionStruct) {
            if(PositionHelper::isDiscount($item) && $this->useFallbackDiscount == false) {
                $this->addDiscountItem($item);
                return;
            }
            $name = $item->getName();
            $productNumber = $item->getNumber();
            $itemQuantity = $quantity ? : $item->getQuantity();
            $price = TaxHelper::getItemGrossPrice($this->paymentRequestData, $item);
            $taxRate = TaxHelper::getItemTaxRate($this->paymentRequestData, $item);
        } else {
            throw new \RuntimeException('type '.get_class($item).' is no longer supported');
        }

        $this->basket['Items'][] = [
            'Item' => [
                'Description' => $name,
                'ArticleNumber' => $productNumber,
                'Quantity' => $itemQuantity,
                'UnitPriceGross' => $price,
                'TaxRate' => $taxRate,
            ]
        ];
        $this->simpleItems[$productNumber] = new BasketPosition($productNumber, $itemQuantity);
    }

    public function addShippingItem()
    {
        if($this->order) {
            $shippingCost = TaxHelper::getShippingGrossPrice($this->order);
            $shippingTax = TaxHelper::getShippingTaxRate($this->order);
        } else if($this->paymentRequestData) {
            $shippingCost = TaxHelper::getShippingGrossPrice($this->paymentRequestData);
            $shippingTax = TaxHelper::getShippingTaxRate($this->paymentRequestData);
        } else {
            throw new \RuntimeException('no payment request data or order is available');
        }

        if ($this->useFallbackShipping) {
            //TODO verify if it is necessary to calculate the tax info
            $detail = new Detail();
            $detail->setNumber(BasketPosition::SHIPPING_NUMBER);
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
            $this->simpleItems['shipping'] = 1;
            $this->simpleItems[BasketPosition::SHIPPING_NUMBER] = new BasketPosition(BasketPosition::SHIPPING_NUMBER, 1);
        }
    }

    /**
     * @param PositionStruct|Detail $item
     */
    public function addDiscountItem($item) {
        if(PositionHelper::isDiscount($item) === false) {
            throw new \RuntimeException('given object is not a discount (number: '.$item->getNumber().')');
        }
        if ($this->useFallbackDiscount) {
            $this->addItem($item);
        } else {
            if($item instanceof Detail) {
                $name = $item->getArticleName();
                $productNumber = $item->getArticleNumber();
                $price = TaxHelper::getItemGrossPrice($item->getOrder(), $item);
                $taxRate = TaxHelper::getItemTaxRate($item->getOrder(), $item);
            } else if($item instanceof PositionStruct) {
                $name = $item->getName();
                $productNumber = $item->getNumber();
                $price = TaxHelper::getItemGrossPrice($this->paymentRequestData, $item, $item->getTotal());
                $taxRate = TaxHelper::getItemTaxRate($this->paymentRequestData, $item, $item->getTotal());
            } else {
                // should never occurs cause the function call `PositionHelper::isDiscount`
                // already throw an exception if it is the wrong type
                throw new \RuntimeException('the object must be a type of '.Detail::class.' or '.PositionStruct::class);
            }

            if(isset($this->basket['Discount'])) {
                throw new \RuntimeException('ratepay does not support more than one discount element');
            } else {
                $this->basket['Discount'] = [
                    'Description' => $name,
                    'UnitPriceGross' => $price,
                    'TaxRate' => $taxRate,
                ];
            }
            $this->simpleItems[$productNumber] = new BasketPosition($productNumber, 1);
        }
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
