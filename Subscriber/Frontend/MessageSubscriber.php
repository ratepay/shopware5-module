<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;


use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use RpayRatePay\Services\MessageManager;

class MessageSubscriber implements SubscriberInterface
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure' => 'addMessagesToView'
        ];
    }

    public function addMessagesToView(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $allowedPages = ['confirm', 'cart']; //this are the pages where the message block got displayed/available
        if ($controller->Response()->isRedirect() === false &&
            $controller->Request()->getControllerName() == 'checkout' &&
            in_array($controller->Request()->getActionName(), $allowedPages)) {
            $controller->View()->assign('ratePayMessages', $this->messageManager->getMessages(true));
        }
    }


}
