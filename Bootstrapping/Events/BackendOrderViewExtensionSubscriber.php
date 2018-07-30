<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 11:18
 */
namespace RpayRatePay\Bootstrapping\Events;

class BackendOrderViewExtensionSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_BackendOrderViewExtensionSubscriber constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_SwagBackendOrder' => 'extendBackendOrderView',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     */
    public function extendBackendOrderView(\Enlight_Event_EventArgs $arguments)
    {
        $arguments->getSubject()->View()->addTemplateDir(
            $this->path . 'Views/backend/rpay_ratepay_backend_order/'
        );

        if ($arguments->getRequest()->getActionName() === 'index') {
            $arguments->getSubject()->View()->extendsTemplate(
                'app.js'
            );
        }
    }
}