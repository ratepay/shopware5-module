<?php

namespace RpayRatePay\Tests\Functional\Component;

use RpayRatePay\Component\Service\ValidationLib as ValidationService;
use Shopware\Components\Test\Plugin\TestCase;

class ValidationTest extends TestCase
{
    public function testIsBirthdayValid__tooYoung()
    {
        $this->markTestSKipped('Method does not have expected behavior.');
        $customer = $this->getRandomCustomer();

        Shopware()->Session()->sUserId = $customer->getId();

        $validator = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($customer);
        $oldBirthday = $customer->getBirthday();

        $aDayTooYoung = $this->findTurns18Tomorrow();

        $customer->setBirthday($aDayTooYoung);
        $this->saveModel($customer);

        $this->assertFalse(ValidationService::isBirthdayValid($customer), 'Date tested ' . $aDayTooYoung->format('Y-m-d') .
            ' today: ' . (new \DateTime())->format('Y-m-d'));

        $customer->setBirthday($oldBirthday);
        $this->saveModel($customer);
    }

    public function testIsBirthdayValid__justOldEnough()
    {
        $customer = $this->getRandomCustomer();
        Shopware()->Session()->sUserId = $customer->getId();

        $validator = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($customer);
        $oldBirthday = $customer->getBirthday();

        $oldEnough = $this->findTurns18Today();

        $customer->setBirthday($oldEnough);
        $this->saveModel($customer);

        $this->assertTrue( ValidationService::isBirthdayValid($customer), 'Date tested ' . $oldEnough->format('Y-m-d') .
            ' today: ' . (new \DateTime())->format('Y-m-d'));

        $customer->setBirthday($oldBirthday);
        $this->saveModel($customer);
    }

    private function saveModel($model)
    {
        Shopware()->Models()->persist($model);
        Shopware()->Models()->flush($model);
    }

    private function findTurns18Tomorrow()
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P18Y'));
        $date->add(new \DateInterval('P1D'));
        return $date;
    }

    private function findTurns18Today()
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P18Y'));
        return $date;
    }

    private function getRandomCustomer()
    {
        $ids = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
              ->createQueryBuilder('c')
              ->select('c.id')
              ->getQuery()
              ->getArrayResult();

        shuffle($ids);

        return Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find(array_shift($ids));
    }
}
