<?php
namespace RpayRatePay\Services\Logger;

use RpayRatePay\Models\OrderHistory;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

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
     * @param Order $order
     * @param $message
     * @param string $name
     * @param string $productNumber
     * @param string $quantity
     */
    public function logHistory(Order $order, $message, $name = '', $productNumber = '', $quantity = '')
    {
        $entry = new OrderHistory();
        $entry->setEvent($message);
        $entry->setOrderId($order->getId());
        $entry->setProductName($name);
        $entry->setProductNumber($productNumber);
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
