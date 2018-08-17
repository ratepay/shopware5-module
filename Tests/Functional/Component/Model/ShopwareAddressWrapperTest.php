<?php

namespace RpayRatePay\Tests\Functional\Component\Model;

use RpayRatePay\Component\Model\ShopwareAddressWrapper;
use Shopware\Components\Test\Plugin\TestCase;

class ShopwareAddressWrapperTest extends TestCase
{

    public function testGetCountry()
    {
        $address = $this->getAddressMock();
        $wrapper = new ShopwareAddressWrapper($address, Shopware()->Models());
        $country = $wrapper->getCountry();
        $this->assertEquals($country->getIso(), 'DE');

        if (class_exists('Shopware\Models\Customer\Billing')) {
            $address = $this->getRottenMock(true);
            $wrapper = new ShopwareAddressWrapper($address, Shopware()->Models());
            $country = $wrapper->getCountry();
            $this->assertEquals($country->getIso(), 'DE');
        }

        if (class_exists('Shopware\Models\Customer\Shipping')) {
            $address = $this->getRottenMock(false);
            $wrapper = new ShopwareAddressWrapper($address, Shopware()->Models());
            $country = $wrapper->getCountry();
            $this->assertEquals($country->getIso(), 'DE');
        }

        //test exception for worng object type
        $this->expectException('\Exception');
        $datetime = new \DateTime();
        $wrapper = new ShopwareAddressWrapper($datetime);
        $wrapper->getCountry();
    }


    private function getAddressMock()
    {
        $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')
            ->findOneBy(['iso' => 'DE']);


        $stub = $this->createMock('Shopware\Models\Customer\Address');

        $stub->method('getCountry')
            ->willReturn($country);

        return $stub;
    }

    private function getRottenMock($billing)
    {
        $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')
            ->findOneBy(['iso' => 'DE']);

        $class = $billing ? 'Shopware\Models\Customer\Billing' : 'Shopware\Models\Customer\Shipping';

        $stub = $this->createMock($class);

        $stub->method('getCountryId')
            ->willReturn($country->getId());

        return $stub;
    }
}