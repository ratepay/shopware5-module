<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 11:25
 */
namespace RpayRatePay\Bootstrapping\Events;

class JavascriptSourceSubscriber implements \Enlight\Event\SubscriberInterface
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
        ];
    }

    /**
     * Add base javascripts
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addJsFiles()
    {
        $jsPath = array(
            $this->path . 'Views/responsive/frontend/_public/src/javascripts/jquery.ratepay_checkout.js'
        );

        return new \Doctrine\Common\Collections\ArrayCollection($jsPath);
    }
}