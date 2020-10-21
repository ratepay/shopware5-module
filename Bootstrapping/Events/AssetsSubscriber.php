<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;

class AssetsSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_JavascriptSourceSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles',
            'Theme_Compiler_Collect_Plugin_Less' => 'addLessFiles'
        ];
    }

    /**
     * Add base javascripts
     *
     * @return ArrayCollection
     */
    public function addJsFiles()
    {
        return new ArrayCollection([
            $this->path . 'Views/frontend/_public/src/js/jquery.ratepay_checkout.js'
        ]);
    }

    /**
     * @return ArrayCollection
     */
    public function addLessFiles()
    {
        return new ArrayCollection([
            new LessDefinition(
                [],
                [$this->path . '/Views/frontend/_public/src/less/all.less'],
                $this->path
            ),
        ]);
    }
}
