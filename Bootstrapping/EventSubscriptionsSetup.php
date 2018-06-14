<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 14:30
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_EventSubscriptionsSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        $subscribers = [
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderOperationsSubscriber(),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_TemplateExtensionSubscriber(),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber($this->bootstrap),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_LoggingControllerSubscriber($this->bootstrap),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailControllerSubscriber($this->bootstrap),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_CheckoutValidationSubscriber(),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentFilterSubscriber(),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PluginConfigurationSubscriber($this->bootstrap),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailsProcessSubscriber(),
            new Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_JavascriptSourceSubscriber(),
        ];

        try {
            foreach ($subscribers as $eventSubscriber) {
                // TODO does this injection works in all Shopware versions?
                $this->bootstrap->get('events')->addSubscriber($eventSubscriber);
            }
        } catch (Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception('Can not create events.' . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $this->install();
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}
}