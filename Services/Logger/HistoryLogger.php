<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Logger;

use RpayRatePay\Models\OrderHistory;
use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\Position\Discount as DiscountPosition;
use RpayRatePay\Models\Position\Product as ProductPosition;
use RpayRatePay\Models\Position\Shipping as ShippingPosition;
use Shopware\Components\Model\ModelManager;

class HistoryLogger
{

    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param AbstractPosition $position
     * @param string $quantity
     * @param $message
     */
    public function logHistory(AbstractPosition $position, $quantity, $message)
    {
        $entry = new OrderHistory();
        $entry->setEvent($message);
        if ($position instanceof ProductPosition || $position instanceof DiscountPosition) {
            $entry->setOrderId($position->getOrderDetail()->getOrder()->getId());
            $entry->setProductName($position->getOrderDetail()->getArticleName());
            $entry->setProductNumber($position->getOrderDetail()->getArticleNumber());
        } else if ($position instanceof ShippingPosition) {
            $entry->setOrderId($position->getSOrderId());
            $entry->setProductName('Shipping');                                                             //TODO
            $entry->setProductNumber('Shipping');                                                         //TODO
        }
        $entry->setQuantity($quantity);
        $this->modelManager->persist($entry);
        $this->modelManager->flush($entry);
    }

    /**
     * Returns the stored History for the given Order
     *
     * @param string $orderId
     *
     * @return array
     */
    public function getHistory($orderId)
    {
        return $this->modelManager->getRepository(OrderHistory::class)->findBy(['orderId' => $orderId]);
    }
}
