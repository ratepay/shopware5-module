<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Tests\Functional\Component\Model;

use RpayRatePay\Bootstrapping\Events\PaymentFilterSubscriber;
use Shopware\Components\Test\Plugin\TestCase;

class PaymentFilterSubscriberTest extends TestCase
{
    public function testFilterPayments__returnNullWhenUserEmpty()
    {
        //$address = $this->getRandomAddress();

        unset(Shopware()->Session()->sUserId);

        $paymentSubscriber = $this->getPaymentFilterSubscriberMock(null);

        $eventArgs = $this->getEnlightEventEventArgsMock();

        $returnNothing = $paymentSubscriber->filterPayments($eventArgs);

        $this->assertNull($returnNothing);
    }

    public function testFilterPayments__allPaymentTypesTurnedOff()
    {
        Shopware()->Session()->sUserId = 1;

        Shopware()->Config()->currency = '$';

        $paymentSubscriber = $this->getPaymentFilterSubscriberMock([
            'installment' => ['status' => 1],
            'invoice' => ['status' => 1],
            'debit' => ['status' => 1],
            'installment0' => ['status' => 1]
        ]);

        $eventArgs = $this->getEnlightEventEventArgsMock();

        $returnVal = $paymentSubscriber->filterPayments($eventArgs);

        $this->assertEquals([
            [
                'name' => 'paypal_payment_type'
            ]
        ], $returnVal);
    }

    public function testFilterPayments__allValid()
    {
        return; //work in progress

        //TODO mock the following stuff or refactor PaymentFilterSubscriber
        /*
        Shopware()->Session()->sUserId = 1;

        Shopware()->Config()->currency = 'EUR';

        Shopware()->Modules()->Basket()->
        */
    }

    private function getPaymentFilterSubscriberMock($configResults)
    {
        $mock = $this->getMockBuilder(
            'RpayRatePay\Bootstrapping\Events\PaymentFilterSubscriber'
        )->setMethods(['getRatePayPluginConfigByCountry', ' getValidator'])
         ->getMock();

        $mock->method('getRatePayPluginConfigByCountry')
            ->willReturn($configResults);

        $validatorStub = $this->createMock('Shopware_Plugins_Frontend_RpayRatePay_Component_Validation');

        $validatorStub->method('isRatepayHidden')
            ->willReturn(false);

        $validatorStub->method('isCurrencyValid')
            ->willReturn(true);

        $validatorStub->method('isDeliveryCountryValid')
            ->willReturn(true);

        $validatorStub->method('isBillingAddressSameLikeShippingAddress')
            ->willReturn(true);

        return $mock;
    }

    private function getEnlightEventEventArgsMock()
    {
        $paymentTypes = [
            [
                'name' => 'paypal_payment_type'
            ]
        ];

        $stub = $this->createMock('\Enlight_Event_EventArgs');

        $stub->method('getReturn')
            ->willReturn($paymentTypes);

        return $stub;
    }

    private function getRandomAddress()
    {
        $class = 'Shopware\Models\Customer\Address';
        $ids = Shopware()->Models()->getRepository($class)
            ->createQueryBuilder('c')
            ->select('c.id')
            ->getQuery()
            ->getArrayResult();

        shuffle($ids);

        return Shopware()->Models()->getRepository($class)->find(array_shift($ids));
    }
}
