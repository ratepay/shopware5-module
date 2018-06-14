<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:48
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderDetailControllerSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var Shopware_Components_Plugin_Bootstrap
     */
    private $bootstrap;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_LoggingControllerSubscriber constructor.
     * @param Shopware_Components_Plugin_Bootstrap $bootstrap
     */
    public function __construct($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_RpayRatepayOrderDetail' => 'onOrderDetailBackendController',
        ];
    }

    /**
     * Loads the Backendextentions
     *
     * @return string
     */
    public function onOrderDetailBackendController()
    {
        Shopware()->Template()->addTemplateDir($this->bootstrap->Path() . 'Views/');

        return $this->bootstrap->Path() . "/Controller/backend/RpayRatepayOrderDetail.php";
    }
}