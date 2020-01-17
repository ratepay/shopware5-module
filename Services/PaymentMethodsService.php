<?php


namespace RpayRatePay\Services;


use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Plugin\Plugin;

class PaymentMethodsService
{

    /**
     * @var string
     */
    protected $pluginName;
    /**
     * @var Plugin
     */
    protected $plugin;
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        ModelManager $modelManager,
        $pluginName
    )
    {
        $this->modelManager = $modelManager;
        $this->pluginName = $pluginName;
    }

    public function enableMethods()
    {
        $payments = $this->getPlugin()->getPayments()->toArray();
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $payment->setActive(true);
        }
        $this->modelManager->flush($payments);
    }

    protected function getPlugin()
    {
        return $this->plugin ?: $this->plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => $this->pluginName]);
    }

    protected function setPlugin(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function disableMethods()
    {
        $payments = $this->getPlugin()->getPayments()->toArray();
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $payment->setActive(true);
        }
        $this->modelManager->flush($payments);
    }

    protected function getLockedPaymentMethodsForCustomer(Customer $customer)
    {
        $lockedMethods = $customer->getAttribute()->getRatepayLockedPaymentMethods();
        if($lockedMethods) {
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

    public function lockPaymentMethodForCustomer(Customer $customer, $paymentMethod, \DateTime $dateTime = null)
    {
        $paymentMethod = $paymentMethod instanceof Payment ? $paymentMethod->getName() : $paymentMethod;
        if ($dateTime == null) {
            $dateTime = new \DateTime();
            $dateTime->modify('+2 days');
        }
        $locked = $this->getLockedPaymentMethodsForCustomer($customer);
        $locked[$paymentMethod] = $dateTime->getTimestamp();
        $customer->getAttribute()->setRatepayLockedPaymentMethods(json_encode($locked));
        $this->modelManager->flush($customer->getAttribute());
    }

}
