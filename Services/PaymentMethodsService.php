<?php


namespace RpayRatePay\Services;


use DateTime;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodsService
{

    /** @var self */
    private static $instance = null;

    /**
     * @var ModelManager
     */
    private $modelManager;

    public static function getInstance()
    {
        return self::$instance = (self::$instance ?: new self(Shopware()->Container()));
    }

    private function __construct(ContainerInterface $container)
    {
        $this->modelManager = $container->get('models');
    }

    protected function getLockedPaymentMethodsForCustomer(Customer $customer)
    {
        $lockedMethods = $customer->getAttribute()->getRatepayLockedPaymentMethods();
        if ($lockedMethods) {
            return json_decode($lockedMethods, true) ?: [];
        } else {
            return [];
        }
    }

    public function isPaymentMethodLockedForCustomer(Customer $customer, $paymentMethod)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        $locked = $this->getLockedPaymentMethodsForCustomer($customer);
        return isset($locked[$paymentMethod]) && time() < $locked[$paymentMethod];
    }

    public function lockPaymentMethodForCustomer(Customer $customer, $paymentMethod, DateTime $dateTime = null)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        if ($dateTime == null) {
            $dateTime = new DateTime();
            $dateTime->modify('+2 days');
        }
        $locked = $this->getLockedPaymentMethodsForCustomer($customer);
        $locked[$paymentMethod] = $dateTime->getTimestamp();
        $customer->getAttribute()->setRatepayLockedPaymentMethods(json_encode($locked));
        $this->modelManager->flush($customer->getAttribute());
    }

}
