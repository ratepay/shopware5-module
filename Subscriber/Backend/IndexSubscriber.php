<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;

class IndexSubscriber implements SubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'onPostDispatchBackendIndex'
        );
    }

    public function onPostDispatchBackendIndex(Enlight_Controller_ActionEventArgs $args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched() ||
            $response->isException() ||
            $request->getActionName() !== 'index' ||
            !$view->hasTemplate()
        ) {
            return;
        }

        $view->extendsTemplate('backend/index/ratepay_header.tpl');
    }
}