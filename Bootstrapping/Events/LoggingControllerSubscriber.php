<?php

namespace RpayRatePay\Bootstrapping\Events;

class LoggingControllerSubscriber implements \Enlight\Event\SubscriberInterface
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
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_RpayRatepayLogging' => 'onLoggingBackendController',
        ];
    }

    /**
     * Loads the Backendextentions
     *
     * @return string
     */
    public function onLoggingBackendController()
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/');

        return $this->path . 'Controller/backend/RpayRatepayLogging.php';
    }
}
