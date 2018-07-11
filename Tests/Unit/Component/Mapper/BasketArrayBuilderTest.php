<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 11.07.18
 * Time: 10:30
 */
use PHPUnit\Framework\TestCase;

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
                new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_BasketArrayBuilder(false, null, false, false),
                $itemList,
                []
            ],
        ];
    }
}