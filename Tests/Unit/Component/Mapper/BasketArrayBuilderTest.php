<?php

use PHPUnit\Framework\TestCase;
use Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_BasketArrayBuilder as BasketArrayBuilder;

class BasketArrayBuilderTest extends TestCase
{
    /**
     * @param $basketBuilder
     * @param $itemList
     * @param $expected
     * @dataProvider provideArrayItems
     */
    public function testAddArrayItem($basketBuilder, $itemList, $expected)
    {
        foreach ($itemList as $item) {
            $basketBuilder->addItem($item);
        }

        $basket = $basketBuilder->toArray();
        $this->assertEquals($expected, $basket);
    }

    public function provideArrayItems()
    {
        $itemList = [];

        return [
            [
                new BasketArrayBuilder(false, null, false, false),
                $itemList,
                []
            ],
        ];
    }
}
