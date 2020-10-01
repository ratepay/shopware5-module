<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;

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
                new BasketArrayBuilder(false, null, false, false, false),
                $itemList,
                []
            ],
        ];
    }
}
