<?php

namespace RpayRatePay\Event;


use Enlight_Event_EventArgs;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use Shopware\Models\Order\Detail;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class FilterBasketItem extends Enlight_Event_EventArgs
{
    /**
     * @var array $item
     * @var Detail|PositionStruct|null
     */
    private $originalSourceItem;

    /**
     * @var BasketArrayBuilder
     */
    private $builder;

    /**
     * @param Detail|PositionStruct|null $originalSourceItem
     */
    public function __construct(BasketArrayBuilder $builder, $originalSourceItem)
    {
        parent::__construct([]);
        $this->originalSourceItem = $originalSourceItem;
        $this->builder = $builder;
    }

    /**
     * @return Detail|PositionStruct|null
     */
    public function getOriginalSourceItem()
    {
        return $this->originalSourceItem;
    }

    /**
     * @return BasketArrayBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return array{Description: string, ArticleNumber: string, UniqueArticleNumber: string, Quantity: int, UnitPriceGross: float, TaxRate: float}
     */
    public function getReturn()
    {
        return parent::getReturn();
    }
}
