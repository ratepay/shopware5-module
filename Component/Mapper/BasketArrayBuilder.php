<?php
namespace RpayRatePay\Component\Mapper;
use RatePAY\Service\Math;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Currency;

class BasketArrayBuilder
{
    /**
     * @var array
     */
    protected $basket;

    /**
     * @var null
     */
    protected $requestType;

    /**
     * @var bool
     */
    protected $pricesAreInNet;

    /**
     * @var bool
     */
    protected $useFallbackShipping;

    /** @var bool  */
    protected $useFallbackDiscount;

    /**
     * BasketArrayBuilder constructor.
     * @param Order|PaymentRequestData $data
     * @param array $items
     * @param null $requestType
     */
    public function __construct($data, array $items = null, $requestType = null)
    {
        $configService = Shopware()->Container()->get(ConfigService::class); //TODO
        $modelManager = Shopware()->Container()->get('models'); //TODO


        $this->basket = [];
        $this->requestType = $requestType;

        if($data instanceof Order) {
            $this->basket['Currency'] = $data->getCurrency();
            $this->pricesAreInNet = $data->getNet() === 1;
            $this->useFallbackShipping = $data->getAttribute()->getRatepayFallbackShipping() == 1;
            $this->useFallbackDiscount = $data->getAttribute()->getRatepayFallbackDiscount() == 1;
        } else if($data instanceof PaymentRequestData) {
            /** @var PaymentRequestData $paymentRequestData */
            $paymentRequestData = $data;
            $this->basket['Currency'] = $modelManager->find(Currency::class, $paymentRequestData->getCurrencyId())->getCurrency();
            $this->pricesAreInNet = $paymentRequestData->isItemsAreInNet();
            $this->useFallbackShipping = $configService->isCommitShippingAsCartItem($paymentRequestData->getShop()->getId());
            $this->useFallbackDiscount = $configService->isCommitDiscountAsCartItem($paymentRequestData->getShop()->getId());
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
     * @param $item
     * @deprecated this is an anti-pattern
     */
    public function addItem($item)
    {
        if (is_array($item)) {
            $this->addItemFromArray($item);
        } elseif ($this->isShippingItem($item) && $this->isBoughtItem($item)) {
            $this->addShippingItem($item);
        } elseif ($this->isDiscountItem($item) && $this->isBoughtItem($item)) {
            $this->addDiscountItem($item);
        } elseif ($this->isDebitItem($item) || $this->isCreditItem($item)) {
            $this->addItemForCreditItem($item);
        } elseif (is_object($item)) {
            $this->addItemFromObject($item);
        }
    }

    /**
     * @param $item
     */
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
     */
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
     */
    private function addShippingItem($item)
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

    private function addDiscountItem($item) {
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
     */
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
     */
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
     */
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
     */
    public function isShippingItem($item)
    {
        return 'shipping' === $item->articlenumber;
    }

    /**
     * @param $item
     * @return bool
     */
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
     */
    public function isDebitItem($item)
    {
        return 'Debit' === substr($item->articlenumber, 0, 5);
    }

    /**
     * @param $item
     * @return bool
     */
    public function isCreditItem($item)
    {
        return 'Credit' === substr($item->articlenumber, 0, 6);
    }

    /**
     * @param $item
     * @return bool
     */
    public function isBoughtItem($item)
    {
        return (0 == $item->delivered || 0 == $item->cancelled || 0 == $item->returned);
    }

    /**
     * @param $item
     * @return bool
     */
    private function itemObjectHasName($item)
    {
        return isset($item->name);
    }

    /**
     * @param $item
     * @return bool
     */
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
     */
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
}
