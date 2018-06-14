<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_PaymentsSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        try {
            $paymentMethods = $this->loadConfig('payment_methods_install.json');
            foreach ($paymentMethods as $payment) {
                $payment['pluginID'] = $this->bootstrap->getId();
                $this->bootstrap->createPayment($payment);
            }
        } catch (Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception("Can not create payment." . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $paymentMethods = $this->loadConfig('payment_methods_update.json');
        foreach ($paymentMethods as $payment) {
            $payment['pluginID'] = $this->bootstrap->getId();
            $this->bootstrap->createPayment($payment);
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}
}