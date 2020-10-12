<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping;

class PaymentsSetup extends Bootstrapper
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        try {
            $paymentMethods = $this->loadConfig('payment_methods_install.json');
            foreach ($paymentMethods as $payment) {
                $payment['pluginID'] = $this->bootstrap->getId();
                $this->bootstrap->createPayment($payment);
            }
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new \Exception('Can not create payment.' . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $paymentMethods = $this->loadConfig('payment_methods_update.json');
        foreach ($paymentMethods as $payment) {
            $payment['pluginID'] = $this->bootstrap->getId();
            $this->bootstrap->createPayment($payment);
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }
}
