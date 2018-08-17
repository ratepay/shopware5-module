<?php

namespace RpayRatePay\Tests\Functional\Component\Model;

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use Shopware\Components\Test\Plugin\TestCase;

class ShopwareCustomerWrapperTest extends TestCase
{

    public function testGetBilling()
    {
        $this->_testGetBilling__fresh();
        $this->_testGetBillingCountry__fresh();

        if (!class_exists('Shopware\Models\Customer\Billing')) {
            return;
        }

        $this->_testGetBilling__rotten();
        $this->_testGetBillingCountry__rotten();
    }

    private function _testGetBilling__fresh()
    {
        $mock = $this->getCustomerFreshMock();
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

    private function _testGetBillingCountry__fresh()
    {
        $mock = $this->getCustomerFreshMock();
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $country = $wrapper->getBillingCountry();

        $this->assertEquals(
            $country->getIso(),
            'BC'
        );
    }

    private function _testGetBilling__rotten()
    {
        $mock = $this->getCustomerRottenMock();
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $this->assertEquals(
            $wrapper->getBilling('phone'),
            '1111111111'
        );

        $billing = $wrapper->getBilling();
        $this->assertNotNull($billing->getCountryId());

    }

    private function _testGetBillingCountry__rotten()
    {
        return;
        $mock = $this->getCustomerRottenMock();
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $country = $wrapper->getBillingCountry();

        $this->assertEquals(
            $country->getIso(),
            'DE'
        );
    }

    public function _testGetShipping__fresh()
    {
        $mock = $this->getCustomerFreshMock(false);
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $this->assertEquals(
            $wrapper->getShipping('phone'),
            '015106483257'
        );

        $shipping = $wrapper->getShipping();

        $this->assertEquals(
            $shipping->getCountry()->getIso(),
            'BC'
        );
    }

    public function _testGetShippingCountry__fresh()
    {
        $mock = $this->getCustomerFreshMock(false);
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $country = $wrapper->getShippingCountry();

        $this->assertEquals(
            $country->getIso(),
            'BC'
        );
    }

    public function testGetShipping()
    {
        $this->_testGetShipping__fresh();
        $this->_testGetShippingCountry__fresh();

        if (!class_exists('Shopware\Models\Customer\Shipping')) {
            return;
        }

        $this->_testGetShipping__rotten();
        $this->_testGetShippingCountry__rotten();
    }

    private function _testGetShipping__rotten()
    {
        $mock = $this->getCustomerRottenMock(false);
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $this->assertEquals(
            $wrapper->getShipping('phone'),
            '1111111111'
        );

        $shipping = $wrapper->getShipping();
        $this->assertNotNull($shipping->getCountryId());
    }

    private function _testGetShippingCountry__rotten()
    {
        $mock = $this->getCustomerRottenMock(false);
        $wrapper = new ShopwareCustomerWrapper($mock, Shopware()->Models());

        $country = $wrapper->getShippingCountry();

        $this->assertEquals(
            $country->getIso(),
            'DE'
        );
    }

    private function getCustomerFreshMock($billingTest = true)
    {
        $country = new \Shopware\Models\Country\Country();
        $country->setIso('BC');

        $defaultBillingAddress = new \Shopware\Models\Customer\Address();
        $defaultBillingAddress->setPhone('015106483257');
        $defaultBillingAddress->setCountry($country);

        $stub = $this->createMock('Shopware\Models\Customer\Customer');

        $method = $billingTest ? 'getDefaultBillingAddress' : 'getDefaultShippingAddress';

        $stub->method($method)
             ->willReturn($defaultBillingAddress);

        return $stub;
    }

    private function getCustomerRottenMock($billingTest = true)
    {
        $country = new \Shopware\Models\Country\Country();
        $country->setIso('BC');

        $defaultBilling = new \Shopware\Models\Customer\Billing();
        $defaultBilling->setPhone('1111111111');


        $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')
            ->findOneBy(['iso' => 'DE']);

        $defaultBilling->setCountryId($country->getId());

        $stub = $this->createMock('Shopware\Models\Customer\Customer');

        $method = $billingTest ? 'getBilling' : 'getShipping';

        $stub->method($method)
            ->willReturn($defaultBilling);

        return $stub;
    }
}