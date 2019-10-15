<?php


namespace RpayRatePay\Bootstrap;

use RpayRatePay\Enum\PaymentMethods;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Payment\Payment;

class PaymentMeans extends AbstractBootstrap
{
    /**
     * @var PaymentInstaller
     */
    protected $paymentInstaller;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        InstallContext $context,
        ModelManager $modelManager,
        PaymentInstaller $paymentInstaller
    )
    {
        parent::__construct($context);
        $this->modelManager = $modelManager;
        $this->paymentInstaller = $paymentInstaller;
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
    }

    public function update()
    {
        $this->install();
    }

    public function uninstall($keepUserData = false)
    {
        $this->deactivate();
    }

    public function activate()
    {
        $this->setActiveFlag(true);
    }

    public function deactivate()
    {
        $this->setActiveFlag(false);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($active)
    {
        //we could use the PaymentMethodsService, but the services are not available while the install process
        $payments = $this->installContext->getPlugin()->getPayments()->toArray();
        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $this->modelManager->flush($payments);
    }
}
