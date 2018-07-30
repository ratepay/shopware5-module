<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 06.07.18
 * Time: 10:57
 */

// namespace RpayRatePay\Component\Mapper;

class  Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_BasketArrayBuilder
{
    /**
     * @var array
     */
    protected $basket;

    /**
     * @var bool
     */
    protected $withRetry;

    /**
     * @var null
     */
    protected $requestType;

    /**
     * @var bool
     */
    protected $allowNetPrice;

    /**
     * @var bool
     */
    protected $useFallbackShipping;

    public function __construct($withRetry = false, $requestType = null, $allowNetPrice = false, $useFallbackShipping = false)
    {
        $this->basket = [];
        $this->withRetry = $withRetry;
        $this->requestType = $requestType;
        $this->allowNetPrice = $allowNetPrice;
        $this->useFallbackShipping = $useFallbackShipping;
    }

    public function toArray()
    {
        return $this->basket;
    }

    /**
     * @param $item
     */
    public function addItem($item)
    {
        if (is_array($item)) {
            $this->addItemFromArray($item);
        } elseif ($this->isShippingItem($item) && $this->isBoughtItem($item)) {
            $this->addShippingItem($item);
        } elseif ($this->isDebitItem($item) || $this->isCreditItem($item)) {
            $this->addItemForCreditItem($item);
        } elseif (is_object($item)) {
            $this->addItemFromObject($item);
        }
    }

    /**
     * @param $item
     */
    private function addItemFromArray($item)
    {
        if (!$this->requestType && $item['quantity'] == 0) {
            return;
        }

        if ('Shipping' === $item['articlename']) {
            $this->addShippingItemFromArray($item);
            return;
        }

        $itemData = [
            'Description' => $item['articlename'],
            'ArticleNumber' => $item['ordernumber'],
            'Quantity' => $item['quantity'],
            'UnitPriceGross' => $item['priceNumeric'],
            'TaxRate' => $item['tax_rate'],
        ];

        if ($this->allowNetPrice) {
             $price = $item['priceNumeric'] / 100 * $item['tax_rate'] + $item['priceNumeric'];
             $itemData['UnitPriceGross'] = $item['priceNumeric'];
        }

        $this->basket['Items'][] = ['Item' => $itemData];
    }

    /**
     * @param $item
     */
    private function addShippingItemFromArray($item)
    {
        if ($this->withRetry || $this->useFallbackShipping) {
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
     */
    private function addShippingItem($item)
    {
        if ($this->withRetry || $this->useFallbackShipping) {
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
     */
    private function addItemForCreditItem($item)
    {
        if ($this->withRetry || $item->price > 0) {
            $item = [
                'ArticleNumber' => $item->articleordernumber,
                'Quantity' => $item->quantity,
                'Description' => $item->articlenumber,
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
        } else {
            $this->basket['Discount'] = [
                'Description' => $item->articlenumber,
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            ];
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

        $itemData = null;
        if (!$this->itemObjectHasName($item)) {
            $itemData = $this->getUnnamedItem($item);
        } else {
            $itemData = array(
                'Description' => $item->name,
                'ArticleNumber' => $item->articlenumber,
                'Quantity' => $item->quantity,
                'UnitPriceGross' => $item->price,
                'TaxRate' => $item->taxRate,
            );
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
        $itemData = [
            'Description' => $item->getArticleName(),
            'ArticleNumber' => $item->getArticleNumber(),
            'Quantity' => $item->getQuantity(),
            'UnitPriceGross' => $item->getPrice(),
            'TaxRate' => $item->getTaxRate(),
        ];

        if ($this->allowNetPrice) {
            $itemData['UnitPriceGross'] = $item->getNetPrice();
        }

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
        return $item->quantity == 0;
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
                $quantity = ($item->maxQuantity == 0) ? 0 : $item->maxQuantity;
                break;
        }

        return $quantity;
    }
}