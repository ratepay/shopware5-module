<?php

namespace RpayRatePay\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use RpayRatePay\Enum\PaymentMethods;

class PaymentMethodClassesSubscriber implements SubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_InitiatePaymentClass_AddClass' => 'addPaymentMethodClasses'
        ];
    }

    public function addPaymentMethodClasses(Enlight_Event_EventArgs $args)
    {
        $classes = $args->getReturn();
        foreach (PaymentMethods::PAYMENTS as $name => $method) {
            $classes[$name] = $method['real_class'];
        }
        $args->setReturn($classes);
    }

}
