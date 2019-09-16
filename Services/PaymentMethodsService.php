<?php


namespace RpayRatePay\Services;


use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin as PluginBootstrap;
use Shopware\Kernel;
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

    public function enableMethods() {
        $payments = $this->getPlugin()->getPayments()->toArray();
        /** @var Payment $payment */
        foreach($payments as $payment) {
            $payment->setActive(true);
        }
        $this->modelManager->flush($payments);
    }

    public function disableMethods() {
        $payments = $this->getPlugin()->getPayments()->toArray();
        /** @var Payment $payment */
        foreach($payments as $payment) {
            $payment->setActive(true);
        }
        $this->modelManager->flush($payments);
    }

    protected function getPlugin() {
        return $this->plugin ? : $this->plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => $this->pluginName]);
    }
    protected function setPlugin(Plugin $plugin) {
        $this->plugin = $plugin;
    }

}
