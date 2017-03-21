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
                $shopId = $order->getShop()->getId();

                //if shop id is not set then use main shop and set config
                if(!$shopId) $shopId = 1;
                $config = array();
                $config['shop'] = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $shopId);
                $config['db'] = Shopware()->Db();
                $this->_config = new \Shopware_Components_Config($config);

                //get user of current order and set sandbox mode
                $orderUser    = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $order->getCustomer()->getId());
                $orderCountry = Shopware()->Models()->find(
                    'Shopware\Models\Country\Country',
                    $orderUser->getBilling()->getCountryId()
                );

                $sandbox = $this->_config['RatePaySandbox' . $orderCountry->getIso()];

                //set sandbox mode in model
                $this->_service = new Shopware_Plugins_Frontend_RpayRatePay_Component_Service_RequestService($sandbox);

            }

            $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory($this->_config);
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
            $total = Shopware()->Db()->fetchOne("SELECT count(*) FROM `s_order_details` WHERE `s_order_details`.`orderId`=?;", array($orderId));

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
            $itemsToDeliver = null;

            $basketItems = array();
            foreach ($items as $item) {
                $basketItem = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();

                $itemsToDeliver += $item->deliveredItems;

                if ($item->quantity == 0) {
                    continue;
                }
                $basketItem->setArticleName($item->name);
                $basketItem->setArticleNumber($item->articlenumber);
                $basketItem->setQuantity($item->quantity);
                $basketItem->setTaxRate($item->taxRate);
                $basketItem->setUnitPriceGross($item->price);
                $basketItems[] = $basketItem;
            }

            if ($itemsToDeliver > 0) {

                $confirmationDeliveryModel = $this->_modelFactory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_ConfirmationDelivery(), $orderId);

                $documentModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document');
                $document = $documentModel->findOneBy(array('orderId' => $orderId, 'type' => 1));
                if (!is_null($document)) {
                    $dateObject = new DateTime();
                    $currentDate = $dateObject->format("Y-m-d");
                    $currentTime = $dateObject->format("H:m:s");
                    $currentDateTime = $currentDate . "T" . $currentTime;
                    /* Add due date after implementation of due date config data
                     *
                     * $dueDate = $dateObject->add(new DateInterval("P" . DueDate . "D"))->format("Y-m-d");
                     * $dueDateTime = $dueDate . "T" . $currentTime;
                     */

                    $invoicing = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_Invoicing();
                    $invoicing->setInvoiceId($document->getDocumentId());
                    $invoicing->setInvoiceDate($currentDateTime);
                    $invoicing->setDeliveryDate($currentDateTime);
                    //$invoicing->setDueDate($dueDateTime);
                    $confirmationDeliveryModel->setInvoicing($invoicing);
                }

                $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
                $basket->setAmount($this->getRecalculatedAmount($basketItems));
                $basket->setCurrency($order->getCurrency());
                $basket->setItems($basketItems);
                $confirmationDeliveryModel->setShoppingBasket($basket);
                
                $head = $confirmationDeliveryModel->getHead();
                $head->setTransactionId($order->getTransactionID());
                $confirmationDeliveryModel->setHead($head);
                
                $response = $this->_service->xmlRequest($confirmationDeliveryModel->toArray());
                $result = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('CONFIRMATION_DELIVER', $response);
                if ($result === true) {
                    foreach ($items as $item) {
                        $bind = array(
                            'delivered' => $item->delivered + $item->deliveredItems
                        );
                        $this->updateItem($orderId, $item->articlenumber, $bind);
                        if ($item->quantity <= 0) {
                            continue;
                        }
                        $this->_history->logHistory($orderId, "Artikel wurde versand.", $item->name, $item->articlenumber, $item->quantity);
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

            $order = Shopware()->Db()->fetchRow("SELECT * FROM `s_order` WHERE `id`=?", array($orderId));
            $basketItems = array();

            $itemsToCancel = null;
            foreach ($items as $item) {
                $basketItem = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();

                // count all item which are in cancellation process
                $itemsToCancel += $item->cancelledItems;

                if ($item->quantity <= 0) {
                    continue;
                }

                $basketItem->setArticleName($item->name);
                $basketItem->setArticleNumber($item->articlenumber);
                $basketItem->setQuantity($item->quantity);
                $basketItem->setTaxRate($item->taxRate);
                $basketItem->setUnitPriceGross($item->price);
                $basketItems[] = $basketItem;
            }

            //only call the logic if there are items to cancel
            if($itemsToCancel > 0)
            {

                $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
                $basket->setAmount($this->getRecalculatedAmount($basketItems));
                $basket->setCurrency($order['currency']);
                $basket->setItems($basketItems);

                $subtype = 'partial-cancellation';
                $this->_modelFactory->setTransactionId($order['transactionID']);
                $paymentChange = $this->_modelFactory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange(), $orderId);
                $head = $paymentChange->getHead();
                $head->setOperationSubstring($subtype);
                $paymentChange->setHead($head);
                $paymentChange->setShoppingBasket($basket);

                $response = $this->_service->xmlRequest($paymentChange->toArray());
                $result = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('PAYMENT_CHANGE', $response);
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
            $order = Shopware()->Db()->fetchRow("SELECT * FROM `s_order` WHERE `id`=?", array($orderId));
            $itemsToReturn = null;
            $basketItems = array();

            foreach ($items as $item) {
                $basketItem = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();

                // count all item which are in returning process
                $itemsToReturn += $item->returnedItems;

                if ($item->quantity <= 0) {
                    continue;
                }
                $basketItem->setArticleName($item->name);
                $basketItem->setArticleNumber($item->articlenumber);
                $basketItem->setQuantity($item->quantity);
                $basketItem->setTaxRate($item->taxRate);
                $basketItem->setUnitPriceGross($item->price);
                $basketItems[] = $basketItem;
            }

            //only call the logic if there are items to return
            if($itemsToReturn > 0)
            {

                $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
                $basket->setAmount($this->getRecalculatedAmount($basketItems));
                $basket->setCurrency($order['currency']);
                $basket->setItems($basketItems);

                $subtype = 'partial-return';

                $this->_modelFactory->setTransactionId($order['transactionID']);
                $paymentChange = $this->_modelFactory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange(), $orderId);
                $head = $paymentChange->getHead();
                $head->setOperationSubstring($subtype);
                $paymentChange->setHead($head);
                $paymentChange->setShoppingBasket($basket);

                $response = $this->_service->xmlRequest($paymentChange->toArray());
                $result = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('PAYMENT_CHANGE', $response);
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
            $basketItems = array();
            foreach ($orderItems as $row) {
                if ($row['quantityDeliver'] == 0) {
                    continue;
                }
                if (strpos($row['articleordernumber'], 'Debit') === false
                    && strpos($row['articleordernumber'], 'Credit') === false
                ) {
                    $onlyDebit = false;
                }
                $basketItem = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();
                $basketItem->setArticleName($row['name']);
                $basketItem->setArticleNumber($row['articleordernumber']);
                $basketItem->setQuantity($row['quantityDeliver']);
                $basketItem->setTaxRate($row['tax_rate']);
                $basketItem->setUnitPriceGross($row['price']);
                $basketItems[] = $basketItem;
            }
            $shippingRow = $this->getShippingFromDBAsItem($orderId);
            if (!is_null($shippingRow) && $shippingRow['quantityDeliver'] != 0) {
                $basketItem = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_item();
                $basketItem->setArticleName($shippingRow['name']);
                $basketItem->setArticleNumber($shippingRow['articleordernumber']);
                $basketItem->setQuantity($shippingRow['quantityDeliver']);
                $basketItem->setTaxRate($shippingRow['tax_rate']);
                $basketItem->setUnitPriceGross($shippingRow['price']);
                $basketItems[] = $basketItem;
            }

            if ($onlyDebit == false) {
                $basket = new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_SubModel_ShoppingBasket();
                $basket->setAmount($this->getRecalculatedAmount($basketItems));
                $basket->setCurrency($order['currency']);
                $basket->setItems($basketItems);

                $this->_modelFactory->setTransactionId($order['transactionID']);
                $paymentChange = $this->_modelFactory->getModel(new Shopware_Plugins_Frontend_RpayRatePay_Component_Model_PaymentChange(), $orderId);
                $head = $paymentChange->getHead();
                $head->setOperationSubstring($subOperation);
                $paymentChange->setHead($head);
                $paymentChange->setShoppingBasket($basket);

                $response = $this->_service->xmlRequest($paymentChange->toArray());
                $result = Shopware_Plugins_Frontend_RpayRatePay_Component_Service_Util::validateResponse('PAYMENT_CHANGE', $response);

                if ($result) {
                    if ($subOperation === 'credit') {
                        if ($row['price'] < 0) {
                            $event = 'Nachbelastunglass wurde hinzugefügt';
                        } else {
                            $event = 'Nachlass wurde hinzugefügt';
                        }
                    } else {
                        $event = 'Artikel wurde hinzugefügt';
                    }

                    foreach ($insertedIds as $id) {
                        $newItems = Shopware()->Db()->fetchRow("SELECT * FROM `s_order_details` WHERE `id`=?", array($id));
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
         * Recalculates the Amount for the given items
         *
         * @param array $items
         *
         * @return float
         */
        private function getRecalculatedAmount($items)
        {
            $basket = array();
            foreach ($items as $item) {
                $detailModel = new \Shopware\Models\Order\Detail();
                $detailModel->setQuantity($item->getQuantity());
                $detailModel->setPrice($item->getUnitPriceGross());
                $detailModel->setTaxRate($item->getTaxRate());
                $detailModel->setArticleName($item->getArticleName());
                $detailModel->setArticleNumber($item->getArticleNumber());
                $basket[] = $detailModel;
            }
            $orderModel = new \Shopware\Models\Order\Order();
            $orderModel->setDetails($basket);
            $orderModel->calculateInvoiceAmount();

            return $orderModel->getInvoiceAmount();
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
         * Counts the open Positions
         *
         * @param string $column
         * @param string $orderId
         *
         * @return int
         */
        private function countOpenPositions($column, $orderId)
        {
            $count = null;
            $sql = "SELECT COUNT(*)"
                   . "FROM `s_order_details` AS `detail` "
                   . "INNER JOIN `rpay_ratepay_order_positions` ON `detail`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` "
                   . "WHERE `$column` != 0 AND `detail`.`orderID` = ?";
            $sqlShipping = "SELECT COUNT(*) "
                           . "FROM `rpay_ratepay_order_shipping` AS `shipping` "
                           . "WHERE `$column` != 0 AND `shipping`.`s_order_id` = ?";
            try {
                $count = Shopware()->Db()->fetchOne($sql, array($orderId));
                $temp = Shopware()->Db()->fetchOne($sqlShipping, array($orderId));
                $count += $temp;
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }

            return $count;
        }

        /**
         * return counted cancelled positions
         *
         * @param $orderId
         *
         * @return null|string
         */
        private function countReturnedPositions($orderId)
        {
            $count = null;
            $sql = "SELECT sum(returned)"
                   . "FROM `s_order_details` AS `detail` "
                   . "INNER JOIN `rpay_ratepay_order_positions` ON `detail`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` "
                   . "WHERE `returned` != 0 AND `detail`.`orderID` = ?";
            $sqlShipping = "SELECT COUNT(*) "
                           . "FROM `rpay_ratepay_order_shipping` AS `shipping` "
                           . "WHERE `returned` != 0 AND `shipping`.`s_order_id` = ?";
            try {
                $count = Shopware()->Db()->fetchOne($sql, array($orderId));
                $temp = Shopware()->Db()->fetchOne($sqlShipping, array($orderId));
                $count += $temp;
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }

            return $count;
        }

        /**
         * return counted cancelled positions
         *
         * @param $orderId
         *
         * @return null|string
         */
        private function countCancelledPositions($orderId)
        {
            $count = null;
            $sql = "SELECT sum(cancelled)"
                   . "FROM `s_order_details` AS `detail` "
                   . "INNER JOIN `rpay_ratepay_order_positions` ON `detail`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` "
                   . "WHERE `cancelled` != 0 AND `detail`.`orderID` = ?";
            $sqlShipping = "SELECT COUNT(*) "
                           . "FROM `rpay_ratepay_order_shipping` AS `shipping` "
                           . "WHERE `cancelled` != 0 AND `shipping`.`s_order_id` = ?";
            try {
                $count = Shopware()->Db()->fetchOne($sql, array($orderId));
                $temp = Shopware()->Db()->fetchOne($sqlShipping, array($orderId));
                $count += $temp;
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }

            return $count;
        }

        /**
         * Counts all Positions of an order
         *
         * @param $orderId
         *
         * @return null|string
         */
        private function countOrderPositions($orderId)
        {
            $count = null;
            $sql      = "SELECT sum(quantity) FROM `s_order_details` WHERE orderID = ?";
            $shipping = "SELECT COUNT(*) FROM `rpay_ratepay_order_shipping` AS `shipping`WHERE `shipping`.`s_order_id` = ?";
            try {
                $count    = Shopware()->Db()->fetchOne($sql, array($orderId));
                $shipping = Shopware()->Db()->fetchOne($shipping, array($orderId));
            } catch (Exception $exception) {
                Shopware()->Pluginlogger()->error($exception->getMessage());
            }

            return $shipping + $count;
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
