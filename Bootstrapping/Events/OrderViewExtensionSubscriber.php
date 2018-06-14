<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 11:18
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_OrderViewExtensionSubscriber implements \Enlight\Event\SubscriberInterface
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
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'extendOrderDetailView',
        ];
    }

    /**
     * extends the Orderdetailview
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function extendOrderDetailView(Enlight_Event_EventArgs $arguments)
    {
        $arguments->getSubject()->View()->addTemplateDir(
            $this->bootstrap->Path() . 'Views/backend/rpay_ratepay_orderdetail/'
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