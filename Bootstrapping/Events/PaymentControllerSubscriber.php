<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;

class PaymentControllerSubscriber implements \Enlight\Event\SubscriberInterface
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
    public function frontendPaymentController(\Enlight_Event_EventArgs $arguments)
    {
        $this->registerMyTemplateDir();

        return $this->path . 'Controller/frontend/RpayRatepay.php';
    }

    protected function registerMyTemplateDir()
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/responsive', 'rpay');
    }
}
