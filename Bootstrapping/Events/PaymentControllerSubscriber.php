<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:38
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentControllerSubscriber implements \Enlight\Event\SubscriberInterface
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
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_RpayRatepay' => 'frontendPaymentController',
        ];
    }

    /**
     * Eventlistener for frontendcontroller
     *
     * @param Enlight_Event_EventArgs $arguments
     *
     * @return string
     */
    public function frontendPaymentController(Enlight_Event_EventArgs $arguments)
    {
        $this->registerMyTemplateDir();

        return $this->bootstrap->Path() . '/Controller/frontend/RpayRatepay.php';
    }

    /**
     * @param bool $isBackend
     */
    protected function registerMyTemplateDir($isBackend = false)
    {
        $this->bootstrap->Application()->Template()->addTemplateDir(__DIR__ . '/Views/responsive', 'rpay');
    }
}