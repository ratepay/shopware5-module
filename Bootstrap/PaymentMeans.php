<?php


namespace RpayRatePay\Bootstrap;

use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\PaymentMethodsService;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Payment\Payment;

class PaymentMeans extends AbstractBootstrap
{
    /**
     * @var PaymentInstaller
     */
    protected $paymentInstaller;

    /**
     * @var PaymentMethodsService
     */
    private $paymentMethodsService;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->paymentInstaller = $this->container->get('shopware.plugin_payment_installer');
        $this->paymentMethodsService = new PaymentMethodsService(
            $this->modelManager,
            $this->installContext->getPlugin()->getName()
        );
    }

    public function update()
    {
        $this->install();
    }

    public function install()
    {
        $repo = $this->modelManager->getRepository(Payment::class);
        foreach (PaymentMethods::PAYMENTS as $options) {
            $payment = $repo->findOneBy(['name' => $options['name']]);
            if ($payment !== null) {
                unset(
                    $options['active'],
                    $options['description'],
                    $options['additionalDescription']
                );
            }
            $this->paymentInstaller->createOrUpdate($this->installContext->getPlugin(), $options);
        }
        $this->paymentMethodsService->enableMethods();
    }

    public function uninstall($keepUserData = false)
    {
        $this->deactivate();
        $this->paymentMethodsService->disableMethods();
    }

    public function deactivate()
    {
        $this->paymentMethodsService->disableMethods();
    }

    public function activate()
    {
        $this->paymentMethodsService->enableMethods();
    }
}
