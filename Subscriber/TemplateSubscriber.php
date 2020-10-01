<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Template_Manager;

class TemplateSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;
    /**
     * @var Enlight_Template_Manager
     */
    protected $template;

    public function __construct(Enlight_Template_Manager $template, $pluginDir)
    {
        $this->template = $template;
        $this->pluginDir = $pluginDir;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch'
        ];
    }

    public function onPreDispatch(Enlight_Event_EventArgs $args)
    {
        $this->template->addTemplateDir($this->pluginDir . '/Resources/views/');
    }
}
