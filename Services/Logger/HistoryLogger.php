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

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * History
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
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
