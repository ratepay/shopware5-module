<?php

namespace RpayRatePay\Tests\Functional\Component\Model;

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use Shopware\Components\Test\Plugin\TestCase;

class ShopwareCustomerWrapperTest extends TestCase
{

    public function testGetBilling__fresh()
    {
        $mock = $this->getCustomerBillingFreshMock();
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $this->assertEquals(
            $wrapper->getBilling('phone'),
            '015106483257'
        );

        $billing = $wrapper->getBilling();

        $this->assertEquals(
            $billing->getCountry()->getIso(),
            'BC'
        );
    }

    public function testGetBillingCountry__fresh()
    {
        $mock = $this->getCustomerBillingFreshMock();
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());


        $country = $wrapper->getBillingCountry();

        $this->assertEquals(
            $country->getIso(),
            'BC'
        );
    }

    private function getCustomerBillingFreshMock()
    {
        $country = new \Shopware\Models\Country\Country();
        $country->setIso('BC');

        $defaultBillingAddress = new \Shopware\Models\Customer\Address();
        $defaultBillingAddress->setPhone('015106483257');
        $defaultBillingAddress->setCountry($country);

        $stub = $this->createMock('Shopware\Models\Customer\Customer');

        $stub->method('getDefaultBillingAddress')
             ->willReturn($defaultBillingAddress);

        return $stub;
    }
}