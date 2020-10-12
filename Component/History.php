<?php

    /**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_History
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
