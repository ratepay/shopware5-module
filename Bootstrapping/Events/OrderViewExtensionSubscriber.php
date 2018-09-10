<?php

namespace RpayRatePay\Bootstrapping\Events;

class OrderViewExtensionSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderViewExtensionSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'extendOrderDetailView',
        ];
    }

    /**
     * Extends the Order-details view
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function extendOrderDetailView(\Enlight_Event_EventArgs $arguments)
    {
        $arguments->getSubject()->View()->addTemplateDir(
            $this->path . 'Views/backend/rpay_ratepay_orderdetail/'
        );

        if ($arguments->getRequest()->getActionName() === 'load') {
            $arguments->getSubject()->View()->extendsTemplate(
                'backend/order/view/detail/ratepaydetailorder.js'
            );
        }

        if ($arguments->getRequest()->getActionName() === 'index') {
            $arguments->getSubject()->View()->extendsTemplate(
                'backend/order/ratepayapp.js'
            );
        }
    }
}
