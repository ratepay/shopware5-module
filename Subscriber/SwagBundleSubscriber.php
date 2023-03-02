<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber;


use Enlight\Event\SubscriberInterface;
use Shopware\Models\Order\Detail;

class SwagBundleSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'ratepay_basket_builder_add_item' => 'addItem'
        ];
    }

    public function addItem(\Enlight_Event_EventArgs $args)
    {
        /** @var array{Description: string, ArticleNumber: string, Quantity: int, UnitPriceGross: float, TaxRate: float} $itemData */
        $itemData = $args->getReturn();
        /** @var Detail|array $itemToAdd */
        $itemToAdd = $args->get('item');
        /** @var null|array $originalItem */
        $originalItem = $args->get('originalItem');

        $packageId = null;
        if ($itemToAdd instanceof Detail && is_array($originalItem) && isset($originalItem['bundlePackageId'])) {
            // frontend request
            $packageId = $originalItem['bundlePackageId'];
        } else if ($itemToAdd instanceof Detail && $itemToAdd->getAttribute() && method_exists($itemToAdd->getAttribute(), 'getBundlePackageId')) {
            // order management (ship/cancel/return)
            $packageId = $itemToAdd->getAttribute()->getBundlePackageId();
        }

        if (!empty($packageId)) {
            $itemData['ArticleNumber'] .= '-bundle-' . $packageId;
        }

        return $itemData;
    }
}
