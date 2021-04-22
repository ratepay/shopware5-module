<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware_Controllers_Backend_Order;
use Shopware_Controllers_Backend_SwagBackendOrder;

class OrderViewExtensionSubscriber implements SubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'extendOrderDetailView',
            'Enlight_Controller_Action_PostDispatch_Backend_SwagBackendOrder' => 'extendSwagBackendOrderView',
        ];
    }

    public function extendSwagBackendOrderView(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_SwagBackendOrder $controller */
        $controller = $args->getSubject();

        if ($controller->Request()->getActionName() === 'index') {
            $controller->View()->extendsTemplate('backend/ratepay_backend_order/includes.js');
            $controller->View()->extendsTemplate('backend/ratepay_logging/app.js');
            $controller->View()->extendsTemplate('backend/ratepay_order_history/app.js');
        }
    }

    /**
     * Extends the Order-details view
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function extendOrderDetailView(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        if ($controller->Request()->getActionName() === 'load') {
            $controller->View()->extendsTemplate('backend/ratepay_order/view/detail/window.js');
        }

        if ($controller->Request()->getActionName() === 'index') {
            $controller->View()->extendsTemplate('backend/ratepay_order/includes.js');
        }
    }
}
