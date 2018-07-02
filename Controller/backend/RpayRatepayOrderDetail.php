<?php

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
     * RpayRatepayOrderDetail
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Controllers_Backend_RpayRatepayOrderDetail extends Shopware_Controllers_Backend_ExtJs
    {

        private $_config;
        private $_modelFactory;
        private $_service;
        private $_history;

        /**
         * index action is called if no other action is triggered
         *
         * @return void
         */
        public function init()
        {
            //set correct subshop for backend processes
            $orderId = $this->Request()->getParam("orderId");
            if(null !== $orderId)
            {
                $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);
                $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory($this->_config);
                //get user of current order and set sandbox mode
                $orderUser    = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $order->getCustomer()->getId());
                $orderCountry = Shopware()->Models()->find(
                    'Shopware\Models\Country\Country',
                    $orderUser->getBilling()->getCountryId()
                );
            } else {
                $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
            }
            $this->_history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();
        }

        /**
         * Initiate the PositionDetails for the given Article in the given Order
         */
        public function initPositionsAction()
        {
            $articleNumbers = json_decode($this->Request()->getParam('articleNumber'));
            $orderID = $this->Request()->getParam('orderID');
            $success = true;
            $bindings = array($orderID);
            foreach (array_unique($articleNumbers) as $articleNumber) {
                $sqlCountEntrys = "SELECT `id`, COUNT(*) AS 'count', SUM(`quantity`) AS 'quantity' FROM `s_order_details` "
                                  . "WHERE `orderID`=? "
                                  . "AND `articleordernumber` = ? "
                                  . "ORDER BY `id` ASC";
                try {
                    $row = Shopware()->Db()->fetchRow($sqlCountEntrys, array($orderID, $articleNumber));
                    if ($row['count'] > 1) { // article already in order, update its quantity
                        $sqlUpdate = "UPDATE `s_order_details` SET `quantity`=? WHERE `id`=?";
                        $sqlDelete = "DELETE FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber` = ? AND `id`!=?";
                        Shopware()->Db()->query($sqlUpdate, array($row['quantity'], $row['id']));
                        Shopware()->Db()->query($sqlDelete, array($orderID, $articleNumber, $row['id']));
                    }
                    else {
                        $bindings[] = $articleNumber;
                        $bind .= '?,';
                    }
                } catch (Exception $exception) {
                    $success = false;
                    Shopware()->Pluginlogger()->error('Exception:' . $exception->getMessage());
                }
            }

            if (!is_null($bind)) { // add new items to order
                $bind = substr($bind, 0, -1);
                $sqlSelectIDs = "SELECT `id` FROM `s_order_details` "
                                . "WHERE `orderID`=? AND `articleordernumber` IN ($bind) ";
                try {
                    $detailIDs = Shopware()->Db()->fetchAll($sqlSelectIDs, $bindings);
                    foreach ($detailIDs as $row) {
                        $values .= "(" . $row['id'] . "),";
                    }
                    $values = substr($values, 0, -1);
                    $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` "
                                 . "(`s_order_details_id`) "
                                 . "VALUES " . $values;
                    Shopware()->Db()->query($sqlInsert);
                } catch (Exception $exception) {
                    $success = false;
                    Shopware()->Pluginlogger()->error('Exception:' . $exception->getMessage(), " SQL:" . $sqlInsert);
                }
            }


            $this->View()->assign(
                array(
                    "success" => $success
                )
            );
        }

        /**
         * Loads the History for the given Order
         */
        public function loadHistoryStoreAction()
        {
            $orderId = $this->Request()->getParam("orderId");
            $history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();
            $historyData = $history->getHistory($orderId);
            $this->View()->assign(
                array(
                    "data"    => $historyData,
                    "success" => true
                )
            );
        }

        /**
         * This Action loads the data from the datebase into the backendview
         */
        public function loadPositionStoreAction()
        {
            $orderId = $this->Request()->getParam("orderId");
            $zero = $this->Request()->getParam("setToZero");
            $data = $this->getFullBasket($orderId);
            $positions = array();
            if ($zero) {
                foreach ($data as $row) {
                    $row['quantityDeliver'] = 0;
                    $row['quantityReturn'] = 0;
                    $positions[] = $row;
                }
            }
            else {
                $positions = $data;
            }
            $total = Shopware()->Db()->fetchOne("SELECT count(*) FROM `s_order_details` WHERE `s_order_details`.`orderID`=?;", array($orderId));

            $this->View()->assign(array(
                    "data"    => $positions,
                    "total"   => $total,
                    "success" => true
                )
            );
        }

        /**
         * Delivers the given Items and assigns the result to the backend
         */
        public function deliverItemsAction()
        {
            $orderId = $this->Request()->getParam("orderId");
            $items = json_decode($this->Request()->getParam("items"));
            $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
            $order = $orderModel->findOneBy(array('id' => $orderId));

            $payment = $order->getPayment()->getName();
            $this->_modelFactory->setTransactionId($order->getTransactionID());
            $this->_modelFactory->setOrderId($order->getNumber());
            $itemsToDeliver = null;

            $basketItems = array();
            $sendItem = true;
            foreach ($items as $item) {
                $itemsToDeliver += $item->deliveredItems;

                if ($payment == 'rpayratepayrate0' || $payment == 'rpayratepayrate') {
                    if (($item->maxQuantity - $item->deliveredItems - $item->cancelled - $item->retournedItems - $item->delivered) !== 0) {
                        $itemsToDeliver += $item->delivered;
                        $sendItem = false;
                    }
                }
            }

            if ($itemsToDeliver > 0) {
                $operationData['orderId'] = $orderId;
                $operationData['method'] = $payment;
                $operationData['items'] = $items;
                $result = false;

                if ($sendItem == true) {
                    $result = $this->_modelFactory->callRequest('ConfirmationDeliver', $operationData);
                }

                if ($result === true || $sendItem == false) {
                    foreach ($items as $item) {
                        $bind = array(
                            'delivered' => $item->delivered + $item->deliveredItems
                        );
                        $this->updateItem($orderId, $item->articlenumber, $bind);
                        if ($item->quantity <= 0) {
                            continue;
                        }

                        if ($sendItem == true) {
                            $this->_history->logHistory($orderId, "Artikel wurde versand.", $item->name, $item->articlenumber, $item->quantity);
                        } else {
                            $this->_history->logHistory($orderId, "Artikel wurde für versand vorbereitet.", $item->name, $item->articlenumber, $item->quantity);
                        }
                    }
                }

                $this->setNewOrderState($orderId, 'delivery');
                $this->View()->assign(array(
                        "result"  => $result,
                        "success" => true
                    )
                );
            } else {
                $this->View()->assign(array(
                        "success" => false
                    )
                );
            }
        }

        /**
         * Cancel the given Items and returns the result to the backend
         */
        public function cancelItemsAction()
        {
            $orderId = $this->Request()->getParam("orderId");
            $items = json_decode($this->Request()->getParam("items"));
            $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
            $order = $orderModel->findOneBy(array('id' => $orderId));
            $this->_modelFactory->setTransactionId($order->getTransactionID());
            $itemsToCancel = null;

            foreach ($items as $item) {
                // count all item which are in cancellation process
                $itemsToCancel += $item->cancelledItems;

                if ($item->quantity <= 0) {
                    continue;
                }
            }

            //only call the logic if there are items to cancel
            if($itemsToCancel > 0)
            {
                $operationData['orderId'] = $orderId;
                $operationData['items'] = $items;
                $operationData['subtype'] = 'cancellation';
                $this->_modelFactory->setOrderId($order->getNumber());
                $result = $this->_modelFactory->callRequest('PaymentChange', $operationData);

                if ($result === true) {
                    foreach ($items as $item) {
                        $bind = array(
                            'cancelled' => $item->cancelled + $item->cancelledItems
                        );
                        $this->updateItem($orderId, $item->articlenumber, $bind);
                        if ($item->cancelledItems <= 0) {
                            continue;
                        }

                        if ($this->Request()->getParam('articleStock') == 1) {
                            $this->_updateArticleStock($item->articlenumber, $item->cancelledItems);
                        }

                        $this->_history->logHistory($orderId, "Artikel wurde storniert.", $item->name, $item->articlenumber, $item->cancelledItems);
                    }
                }
                $this->setNewOrderState($orderId, 'cancellation');
                $this->View()->assign(array(
                        "result"  => $result,
                        "success" => true
                    )
                );
            }
            else
            {
                $this->View()->assign(array(
                        "success" => false
                    )
                );
            }

        }

        /**
         * returns the given Items and returns the result to the backend
         */
        public function returnItemsAction()
        {
            $orderId = $this->Request()->getParam("orderId");
            $items = json_decode($this->Request()->getParam("items"));
            $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
            $order = $orderModel->findOneBy(array('id' => $orderId));
            $this->_modelFactory->setTransactionId($order->getTransactionID());
            $itemsToReturn = null;

            foreach ($items as $item) {
                // count all item which are in returning process
                $itemsToReturn += $item->returnedItems;
                if ($item->quantity <= 0) {
                    continue;
                }
            }

            //only call the logic if there are items to return
            if($itemsToReturn > 0)
            {
                $operationData['orderId'] = $orderId;
                $operationData['items'] = $items;
                $operationData['subtype'] = 'return';
                $this->_modelFactory->setOrderId($order->getNumber());
                $result = $this->_modelFactory->callRequest('PaymentChange', $operationData);

                if ($result === true) {
                    foreach ($items as $item) {
                        $bind = array(
                            'returned' => $item->returned + $item->returnedItems
                        );
                        $this->updateItem($orderId, $item->articlenumber, $bind);
                        if ($item->returnedItems <= 0) {
                            continue;
                        }

                        if ($this->Request()->getParam('articleStock') == 1) {
                            $this->_updateArticleStock($item->articlenumber, $item->returnedItems);
                        }

                        $this->_history->logHistory($orderId, "Artikel wurde retourniert.", $item->name, $item->articlenumber, $item->returnedItems);
                    }
                }

                $this->setNewOrderState($orderId, 'return');

                $this->View()->assign(array(
                        "result"  => $result,
                        "success" => true
                    )
                );
            } else
            {
                $this->View()->assign(array(
                        "success" => false
                    )
                );
            }
        }

        /**
         * Add the given Items to the given order
         */
        public function addAction()
        {
            $onlyDebit = true;
            $orderId = $this->Request()->getParam("orderId");
            $insertedIds = json_decode($this->Request()->getParam("insertedIds"));
            $subOperation = $this->Request()->getParam("suboperation");
            $order = Shopware()->Db()->fetchRow("SELECT * FROM `s_order` WHERE `id`=?", array($orderId));
            $orderItems = Shopware()->Db()->fetchAll("SELECT *, (`quantity` - `delivered` - `cancelled`) AS `quantityDeliver` FROM `s_order_details` "
                                                     . "INNER JOIN `rpay_ratepay_order_positions` ON `s_order_details`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` "
                                                     . "WHERE `orderID`=?", array($orderId));
            foreach ($orderItems as $row) {
                if ($row['quantityDeliver'] == 0) {
                    continue;
                }
                if ((substr($row['articleordernumber'], 0, 5) == 'Debit')
                    || (substr($row['articleordernumber'], 0, 6) == 'Credit')
                ) {
                    $onlyDebit = false;
                }
                $items = $row;
            }

            $shippingRow = $this->getShippingFromDBAsItem($orderId);
            if (!is_null($shippingRow) && $shippingRow['quantityDeliver'] != 0) {
                $items = $shippingRow;
            }

            if ($onlyDebit == false) {
                $this->_modelFactory->setTransactionId($order['transactionID']);
                $operationData['orderId'] = $orderId;
                $operationData['items'] = $items;
                $operationData['subtype'] = 'credit';
                $this->_modelFactory->setOrderId($order['ordernumber']);
                $result = $this->_modelFactory->callRequest('PaymentChange', $operationData);

                if ($result === true) {
                    if ($subOperation === 'credit' || $subOperation === 'debit') {
                        if ($row['price'] > 0) {
                            $event = 'Nachbelastung wurde hinzugefügt';
                        } else {
                            $event = 'Nachlass wurde hinzugefügt';
                        }
                        $bind = array(
                            'delivered' => 1
                        );
                    } else {
                        $event = 'Artikel wurde hinzugefügt';
                    }

                    foreach ($insertedIds as $id) {

                        $newItems = Shopware()->Db()->fetchRow("SELECT * FROM `s_order_details` WHERE `id`=?", array($id));
                        $this->updateItem($orderId, $newItems['articleordernumber'], $bind);

                        if ($newItems['quantity'] <= 0) {
                            continue;
                        }
                        $this->_history->logHistory($orderId, $event, $newItems['name'], $newItems['articleordernumber'], $newItems['quantity']);
                    }
                }
            }
            $this->setNewOrderState($orderId);
            $this->View()->assign(array(
                    "result"  => $result,
                    "success" => true
                )
            );
        }

        /**
         * Updates the given binding for the given article
         *
         * @param string $orderID
         * @param string $articleordernumber
         * @param array  $bind
         */
        private function updateItem($orderID, $articleordernumber, $bind)
        {
            if ($articleordernumber === 'shipping') {
                Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
            }
            else {
                $positionId = Shopware()->Db()->fetchOne("SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?", array($orderID, $articleordernumber));
                Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $positionId);
            }
        }

        /**
         * update the stock of an article
         *
         * @param $article
         * @param $count
         */
        protected function _updateArticleStock($article, $count) {
            $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
            $article = $repository->findOneBy(array('number' => $article));
            $article->setInStock($article->getInStock() + $count);
            Shopware()->Models()->persist($article);
            Shopware()->Models()->flush();
        }

        /**
         * Returns the article for the given id
         */
        public function getArticleAction()
        {
            $id = $this->Request()->getParam('id');
            $data = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->getArticleBaseDataQuery($id)->getArrayResult();
            $data[0]['mainPrices'] = $this->getPrices($data[0]['mainDetail']['id'], $data[0]['tax']);
            $this->View()->assign(
                array(
                    "data"    => $data[0],
                    "success" => true
                )
            );
        }

        /**
         * Returns the Price for the given id
         *
         * @param string $id
         * @param float  $tax
         *
         * @return float
         */
        protected function getPrices($id, $tax)
        {
            $prices = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')
                                ->getPricesQuery($id)
                                ->getArrayResult();

            return $this->formatPricesFromNetToGross($prices, $tax);
        }

        /**
         * Converts the given data from netto to gross
         *
         * @param float $prices
         * @param float $tax
         *
         * @return float
         */
        protected function formatPricesFromNetToGross($prices, $tax)
        {
            foreach ($prices as $key => $price) {
                $customerGroup = $price['customerGroup'];
                if ($customerGroup['taxInput']) {
                    $price['price'] = $price['price'] / 100 * (100 + $tax['tax']);
                    $price['pseudoPrice'] = $price['pseudoPrice'] / 100 * (100 + $tax['tax']);
                }
                $prices[$key] = $price;
            }

            return $prices;
        }

        /**
         * Returns the Shipping as item for the given order
         *
         * @param string $orderId
         *
         * @return array
         */
        private function getShippingFromDBAsItem($orderId)
        {
            $sql = "SELECT "
                   . "`invoice_shipping` AS `price`, "
                   . "(1 - `delivered` - `cancelled`) AS `quantityDeliver`, "
                   . "(`delivered` - `returned`) AS `quantityReturn`, "
                   . "`delivered`, "
                   . "`cancelled`, "
                   . "`returned`, "
                   . "`s_core_tax`.`tax` AS `tax_rate` "
                   . "FROM `s_order` "
                   . "LEFT JOIN `rpay_ratepay_order_shipping` ON `s_order_id`=`s_order`.`id` "
                   . "LEFT JOIN `s_premium_dispatch` ON `s_order`.`dispatchID`=`s_premium_dispatch`.`id` "
                   . "LEFT JOIN `s_core_tax` ON `s_premium_dispatch`.`tax_calculation`=`s_core_tax`.`id` "
                   . "WHERE `s_order`.`id` = ?";
            $shippingRow = Shopware()->Db()->fetchRow($sql, array($orderId));
            if (isset($shippingRow['quantityDeliver'])) {
                if ($shippingRow['tax_rate'] == null) {
                    $shippingRow['tax_rate'] = Shopware()->Db()->fetchOne("SELECT MAX(`tax`) FROM `s_core_tax`");
                }
                $shippingRow['quantity'] = 1;
                $shippingRow['articleID'] = 0;
                $shippingRow['name'] = 'shipping';
                $shippingRow['articleordernumber'] = 'shipping';

                return $shippingRow;
            }
        }

        /**
         * Returns the whole Basket
         *
         * @param string $orderId
         *
         * @return array
         */
        private function getFullBasket($orderId)
        {
            $sql = "SELECT "
                   . "`articleID`, "
                   . "`name`, "
                   . "`articleordernumber`, "
                   . "`price`, "
                   . "`quantity`, "
                   . "(`quantity` - `delivered` - `cancelled`) AS `quantityDeliver`, "
                   . "(`delivered` - `returned`) AS `quantityReturn`, "
                   . "`delivered`, "
                   . "`cancelled`, "
                   . "`returned`, "
                   . "`tax_rate` "
                   . "FROM `s_order_details` AS detail "
                   . "INNER JOIN `rpay_ratepay_order_positions` AS ratepay ON detail.`id`=ratepay.`s_order_details_id` "
                   . "WHERE detail.`orderId`=? "
                   . "ORDER BY detail.`id`;";

            $data = Shopware()->Db()->fetchAll($sql, array($orderId));
            $shipping = $this->getShippingFromDBAsItem($orderId);
            if (!is_null($shipping)) {
                $data[] = $shipping;
            }

            return $data;
        }

        /**
         * Sets the new Orderstate
         *
         * @param boolean $orderComplete
         */
        private function setNewOrderState($orderId, $operation = null)
        {
            $sql = "SELECT COUNT((`quantity` - `delivered` - `cancelled`)) AS 'itemsLeft' "
                   . "FROM `s_order_details` "
                   . "JOIN `rpay_ratepay_order_positions` ON `s_order_details`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` "
                   . "WHERE `orderID`=? AND (`quantity` - `delivered` - `cancelled`) > 0";
            try {
                $orderComplete = Shopware()->Db()->fetchOne($sql, array($orderId));

                if($operation === 'cancellation')
                {
                    $newState = $orderComplete == 0 ? $this->_config['RatePayPartialCancellation'] : $this->_config['RatePayFullCancellation'];
                } elseif($operation === 'delivery') {
                    //only set if order is not partial returned / cancelled
                    if($orderComplete != $this->_config['RatePayPartialReturn'] && $orderComplete != $this->_config['RatePayPartialCancellation'])
                    {
                        $newState = $orderComplete == 0 ? $this->_config['RatePayFullDelivery'] : $this->_config['RatePayPartialDelivery'];
                    }
                } elseif($operation === 'return') {
                    $newState = $orderComplete == 0 ? $this->_config['RatePayFullReturn']: $this->_config['RatePayFullCancellation'];
                }

                // return if no status update
                if(null === $newState)
                {
                    return;
                }

                Shopware()->Db()->update('s_order', array(
                    'status' => $newState
                ), '`id`=' . $orderId);
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }
        }

    }
