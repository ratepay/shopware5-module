<?php

namespace RpayRatePay\Component;
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
 * @deprecated replaced by service
 */
class History
{
    /**
     * Logs the History for an Request
     *
     * @param string $orderId
     * @param string $event
     * @param string $name
     * @param string $articlenumber
     * @param string $quantity
     */
    public function logHistory($orderId, $event, $name = '', $articlenumber = '', $quantity = '')
    {
        $sql = 'INSERT INTO `rpay_ratepay_order_history` '
            . '(`orderId`, `event`, `articlename`, `articlenumber`, `quantity`) '
            . 'VALUES(?, ?, ?, ?, ?)';
        Shopware()->Db()->query($sql, [$orderId, $event, $name, $articlenumber, $quantity]);
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
        $sql = 'SELECT * FROM `rpay_ratepay_order_history`'
            . ' WHERE `orderId`=? '
            . 'ORDER BY `id` DESC';
        $history = Shopware()->Db()->fetchAll($sql, [$orderId]);

        return $history;
    }
}
