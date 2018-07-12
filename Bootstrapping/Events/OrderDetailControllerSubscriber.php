<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:48
 */
namespace Shopware\RatePAY\Bootstrapping\Events;

class OrderDetailControllerSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        Shopware()->Template()->addTemplateDir($this->path . 'Views/');

        return $this->path . "Controller/backend/RpayRatepayOrderDetail.php";
    }
}