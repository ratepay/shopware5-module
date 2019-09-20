<?php
namespace RpayRatePay\Component\Mapper;
use RatePAY\Service\Math;
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
            $this->useFallbackShipping = $configService->isCommitShippingAsCartItem( $this->paymentRequestData->getShop()->getId());
            $this->useFallbackDiscount = $configService->isCommitDiscountAsCartItem( $this->paymentRequestData->getShop()->getId());
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
        $this->simpleItems[$productNumber] = $itemQuantity;
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
            $detail->setNumber('shipping');
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
            $this->simpleItems[$productNumber] = 1;
        }
    }

    /**
     * does return all items in the basket as a simple array
     * key: product number
     * value: quantity
     * @return mixed
     */
    public function getSimpleItems()
    {
        return $this->simpleItems;
    }

    /**
     * @param $item
     * @deprecated
     *\/
    public function addItemFromArray($item)
    {
        if (!$this->requestType && $item['quantity'] == 0) {
            return;
        }

        if ('Shipping' === $item['articlename']) {
            $this->addShippingItemFromArray($item);
            return;
        }

        if ($this->isDiscountItem($item)) {
            $this->addDiscountItemFromArray($item);
            return;
        }

        $swPrice = $item['priceNumeric'];
        $unitPriceGross = $this->pricesAreInNet ? Math::netToGross($swPrice, $item['tax_rate']) : $swPrice;

        $itemData = [
            'Description' => $item['articlename'],
            'ArticleNumber' => $item['ordernumber'], //this looks like a strange key, but is correct in shopware session
            'Quantity' => $item['quantity'],
            'UnitPriceGross' => $unitPriceGross,
            'TaxRate' => $item['tax_rate'],
        ];

        $this->basket['Items'][] = ['Item' => $itemData];
    }

    /**
     * @param $item
     *\/
    private function addShippingItemFromArray($item)
    {
        if ($this->useFallbackShipping) {
            $itemData = [
                'ArticleNumber' => 'shipping',
                'Quantity' => 1,
                'Description' => 'shipping',
                'UnitPriceGross' => $item['priceNumeric'],
                'TaxRate' => $item['tax_rate'],
            ];
            $this->basket['Items'][] = ['Item' => $itemData];
        } else {
            $this->basket['Shipping'] = [
                'Description' => 'Shipping costs',
                'UnitPriceGross' => $item['priceNumeric'],
                'TaxRate' => $item['tax_rate'],
            ];
        }
    }

    /**
     * @param $item
     * @deprecated
     *\/
    private function addDiscountItemFromArray($item){
        if ($this->useFallbackDiscount) {
            $itemData = [
                'ArticleNumber' => $item['ordernumber'],
                'Quantity' => 1,
                'Description' => $item['articlename'],
                'UnitPriceGross' => $item['priceNumeric'],
                'TaxRate' => $item['tax_rate'],
            ];
            $this->basket['Items'][] = ['Item' => $itemData];
        } else {
            if(isset($this->basket['Discount'])) {
                $this->basket['Discount']['UnitPriceGross'] = $this->basket['Discount']['UnitPriceGross'] + $item['priceNumeric'];
                $this->basket['Discount']['Description'] = $this->basket['Discount']['Description'] . ' & ' . $item['articlename'];
            } else {
                $this->basket['Discount'] = [
                    'Description' => $item['articlename'],
                    'UnitPriceGross' => $item['priceNumeric'],
                    'TaxRate' => $item['tax_rate'],
                ];
            }
        }
    }

    /**
     * @param $item
     * @deprecated
     *\/
    private function deprecated___addShippingItem($item)
    {
        //handle partial sending
        if ($this->requestType === 'shipping' || $this->requestType === 'shippingRate') {
            if ((int)($item->quantity) === 0) {
                return;
            }
        }

        //handle partial cancellation or return
        if ($this->requestType === 'cancellation' || $this->requestType === 'return') {
            $amountToCancelOrReturn = $this->getQuantityForRequest($item);
            if ($amountToCancelOrReturn < 1) {
                return;
            }
        }

        if ($this->useFallbackShipping) {
            $itemData = [
                'ArticleNumber' => $item->articlenumber,
                'Quantity' => 1,
                'Description' => 'shipping',
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
            $this->basket['Items'][] = ['Item' => $itemData];
        } else {
            $this->basket['Shipping'] = [
                'Description' => 'Shipping costs',
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
        }
    }

    /**
     * @param $item
     * @deprecated
     *\/
    private function deprecated___addDiscountItem($item) {
        //handle partial sending
        if ($this->requestType === 'shipping' || $this->requestType === 'shippingRate') {
            if ((int)($item->quantity) === 0) {
                return;
            }
        }

        //handle partial cancellation or return
        if ($this->requestType === 'cancellation' || $this->requestType === 'return') {
            $amountToCancelOrReturn = $this->getQuantityForRequest($item);
            if ($amountToCancelOrReturn < 1) {
                return;
            }
        }

        if ($this->useFallbackDiscount) {
            $itemData = [
                'ArticleNumber' => $item->articlenumber,
                'Quantity' => 1,
                'Description' => $item->name,
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
            $this->basket['Items'][] = ['Item' => $itemData];
        } else {
            if(isset($this->basket['Discount'])) {
                $this->basket['Discount']['UnitPriceGross'] = $this->basket['Discount']['UnitPriceGross'] + floatval($item->price);
                $this->basket['Discount']['Description'] = $this->basket['Discount']['Description'] . ' & ' . $item->name;
            } else {
                $this->basket['Discount'] = [
                    'Description' => $item->name,
                    'UnitPriceGross' => floatval($item->price),
                    'TaxRate' => $item->taxRate,
                ];
            }
        }
    }

    /**
     * @param $item
     * @deprecated
     *\/
    private function addItemForCreditItem($item)
    {
        if ($item->price > 0) {
            $item = [
                'ArticleNumber' => $item->articlenumber,
                'Quantity' => 1 , // $item->quantity,
                'Description' => $item->articlenumber,
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
            $this->basket['Items'][] = ['Item' => $item];
        } else {
            $discount = [
                'Description' => $item->articlenumber,
                'UnitPriceGross' => floatval($item->price),
                'TaxRate' => $item->taxRate,
            ];

            if (isset($this->basket['Discount'], $this->basket['Discount']['UnitPriceGross'])) {
                $discount['UnitPriceGross'] = $this->basket['Discount']['UnitPriceGross'] + floatval($item->price);
                $discount['Description'] = $this->basket['Discount']['Description'] . ', ' . $item->articlenumber;
            }

            $this->basket['Discount'] = $discount;
        }
    }

    /**
     * @param $item
     * @deprecated
     *\/
    private function addItemFromObject($item)
    {
        if ($this->hasNoQuantity($item) && empty($this->requestType)) {
            return;
        }

        $swPrice = $item->price;
        $unitPriceGross = $this->pricesAreInNet ? Math::netToGross($swPrice, $item->taxRate) : $swPrice;

        if (!$this->itemObjectHasName($item)) {
            $itemData = $this->getUnnamedItem($item);
        } else {
            $itemData = [
                'Description' => $item->name,
                'ArticleNumber' => $item->articlenumber,
                'Quantity' => $item->quantity,
                'UnitPriceGross' => $unitPriceGross,
                'TaxRate' => $item->taxRate,
            ];
        }

        if (!empty($this->requestType)) {
            $quantity = $this->getQuantityForRequest($item);
            $itemData['Quantity'] = is_null($quantity) ? $itemData['Quantity'] : $quantity;
        }

        if ($itemData['Quantity'] != 0) {
            $this->basket['Items'][] = ['Item' => $itemData];
        }
    }

    /**
     * @param $item
     * @return array
     * @deprecated
     *\/
    private function getUnnamedItem($item)
    {
        $swPrice = $item->getPrice();
        $unitPriceGross = $this->pricesAreInNet ? Math::netToGross($swPrice, $item->taxRate) : $swPrice;

        $itemData = [
            'Description' => $item->getArticleName(),
            'ArticleNumber' => $item->getArticleNumber(),
            'Quantity' => $item->getQuantity(),
            'UnitPriceGross' => $unitPriceGross,
            'TaxRate' => $item->getTaxRate(),
        ];

        //?
        //if ($this->netItemPrices) {
        //    $itemData['UnitPriceGross'] =    // $item->getNetPrice();
        //}

        $this->requestType = false;
        return $itemData;
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    public function isShippingItem($item)
    {
        return 'shipping' === $item->articlenumber;
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    public function isDiscountItem($item)
    {
        if(is_array($item)) {
            if (isset($item['modus'])) {
                return $item['modus'] != 0 && ($item['modus'] != 4 || $item['price'] < 0);
            } else if(isset($item['articlenumber'])) {
                return 'discount' === $item['articlenumber'];
            }
        } else if(is_object($item)) {
            if (isset($item->modus)) {
                return $item->modus != 0 && ($item->modus != 4 || $item->price < 0);
            } else if(isset($item->articlenumber)) {
                return 'discount' === $item->articlenumber;
            }
        }
        return false;
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    public function isDebitItem($item)
    {
        return 'Debit' === substr($item->articlenumber, 0, 5);
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    public function isCreditItem($item)
    {
        return 'Credit' === substr($item->articlenumber, 0, 6);
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    public function isBoughtItem($item)
    {
        return (0 == $item->delivered || 0 == $item->cancelled || 0 == $item->returned);
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    private function itemObjectHasName($item)
    {
        return isset($item->name);
    }

    /**
     * @param $item
     * @return bool
     * @deprecated
     *\/
    private function hasNoQuantity($item)
    {
        if (method_exists($item, 'getQuantity') &&
            $item->getQuantity() == 0) {
            return true;
        }

        if ($item->quantity == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $item
     * @return int|null
     * @deprecated
     *\/
    private function getQuantityForRequest($item)
    {
        $quantity = null;
        switch ($this->requestType) {
            case 'return':
                $quantity = ($item->returnedItems == 0) ? 0 : $item->returnedItems;
                break;
            case 'cancellation':
                $quantity = ($item->cancelledItems == 0) ? 0 : $item->cancelledItems;
                break;
            case 'shippingRate':
                $quantity = ($item->maxQuantity == 0) ? 0 : $item->quantity;
                break;
        }

        return $quantity;
    }

     */
}
