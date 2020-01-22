<?php

use Doctrine\ORM\Query\Expr\Join;
use RatePAY\Model\Response\AbstractResponse;
use RpayRatePay\Component\Service\Logger;
use Shopware\Models\Article\Detail;
use Shopware\Models\Order\Detail as OrderDetail;
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
 * RpayRatepayOrderDetail
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
class Shopware_Controllers_Backend_RpayRatepayOrderDetail extends Shopware_Controllers_Backend_ExtJs
{
    private $_config;

    /** @var Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory */
    private $_modelFactory;
    private $_service;
    private $_history;

    /**
     * index action is called if no other action is triggered
     *
     * @return void
     * @throws \Exception
     */
    public function init()
    {
        //set correct subshop for backend processes
        $orderId = $this->Request()->getParam('orderId');
        if (null !== $orderId) {
            /** @var Order $order */
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $orderId);

            $attributes = $order->getAttribute();
            $backend = (bool)($attributes->getRatepayBackend());
            $netPrices = $order->getNet() === 1;
            $this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory($this->_config, $backend, $netPrices, $order->getShop()->getId());
        } else {
            throw new \Exception('RatepayOrderDetail controller requires parameter orderId');
            //$this->_modelFactory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
        }
        $this->_history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();
    }

    /**
     * Initiate the PositionDetails for the given Article in the given Order
     */
    public function initPositionsAction()
    {
        $articleNumbers = json_decode($this->Request()->getParam('articleNumber'));
        $orderId = $this->Request()->getParam('orderId');
        $success = true;
        $articleNumberToInsert = [];

        $sqlCountEntries = "
          SELECT `id`, COUNT(*) AS 'count', SUM(`quantity`) AS 'quantity' FROM `s_order_details`
          WHERE `orderID`=?
          AND `articleordernumber`=?
          GROUP BY `id`
        ";

        foreach (array_unique($articleNumbers) as $articleNumber) {
            try {
                $row = Shopware()->Db()->fetchRow($sqlCountEntries, [$orderId, $articleNumber]);
                if ($row['count'] > 1) { // article already in order, update its quantity
                    $sqlUpdate = 'UPDATE `s_order_details` SET `quantity`=? WHERE `id`=?';
                    $sqlDelete = 'DELETE FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber` = ? AND `id`!=?';
                    Shopware()->Db()->query($sqlUpdate, [$row['quantity'], $row['id']]);
                    Shopware()->Db()->query($sqlDelete, [$orderId, $articleNumber, $row['id']]);
                } else {
                    $articleNumberToInsert[] = $articleNumber;
                }
            } catch (\Exception $exception) {
                Logger::singleton()->warn('Unable to initialize order position ' . $articleNumber . '. ' . $exception->getMessage());
                $success = false;
            }
        }

        if (!empty($articleNumberToInsert)) { // add new items to order
            $success = $this->insertNewPositionsToOrder($articleNumberToInsert, $orderId);
        }

        $this->View()->assign(compact('success'));
    }

    /**
     * Loads the History for the given Order
     */
    public function loadHistoryStoreAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $history = new Shopware_Plugins_Frontend_RpayRatePay_Component_History();
        $historyData = $history->getHistory($orderId);
        $this->View()->assign(
            [
                'data' => $historyData,
                'success' => true
            ]
        );
    }

    /**
     * This Action loads the data from the datebase into the backendview
     */
    public function loadPositionStoreAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $zero = $this->Request()->getParam('setToZero');
        $data = $this->getFullBasket($orderId);
        $positions = [];
        if ($zero) {
            foreach ($data as $row) {
                $row['quantityDeliver'] = 0;
                $row['quantityReturn'] = 0;
                $positions[] = $row;
            }
        } else {
            $positions = $data;
        }
        $total = Shopware()->Db()->fetchOne('SELECT count(*) FROM `s_order_details` WHERE `s_order_details`.`orderID`=?;', [$orderId]);

        $this->View()->assign(
            [
                'data' => $positions,
                'total' => $total,
                'success' => true
            ]
        );
    }

    /**
     * Delivers the given Items and assigns the result to the backend
     */
    public function deliverItemsAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $items = json_decode($this->Request()->getParam('items'));
        $order = Shopware()->Models()
            ->getRepository('Shopware\Models\Order\Order')
            ->findOneBy(['id' => $orderId]);

        $payment = $order->getPayment()->getName();
        $this->_modelFactory->setTransactionId($order->getTransactionID());
        $this->_modelFactory->setOrderId($order->getNumber());
        $itemsToDeliver = 0;

        $sendItem = true;
        foreach ($items as $item) {
            $itemsToDeliver += $item->deliveredItems;

            if (in_array($payment, ['rpayratepayrate0', 'rpayratepayrate'])) {
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
                $result = $this->_modelFactory->callConfirmationDeliver($operationData);
            }

            if ($result === true || $sendItem == false) {
                foreach ($items as $item) {
                    $bind = [
                        'delivered' => $item->delivered + $item->deliveredItems
                    ];
                    $this->updateItem($orderId, $item->orderDetailId, $bind);
                    if ($item->quantity <= 0) {
                        continue;
                    }

                    if ($sendItem == true) {
                        $this->_history->logHistory($orderId, 'Artikel wurde versand.', $item->name, $item->articlenumber, $item->quantity);
                    } else {
                        $this->_history->logHistory($orderId, 'Artikel wurde für versand vorbereitet.', $item->name, $item->articlenumber, $item->quantity);
                    }
                }
            }

            $this->setNewOrderState($orderId, 'delivery');
            $this->View()->assign(
                [
                    'result' => $result,
                    'success' => true
                ]
            );
        } else {
            $this->View()->assign(
                [
                    'success' => false
                ]
            );
        }
    }

    /**
     * Cancel the given Items and returns the result to the backend
     */
    public function cancelItemsAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $items = json_decode($this->Request()->getParam('items'));
        $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        $order = $orderModel->findOneBy(['id' => $orderId]);
        $this->_modelFactory->setTransactionId($order->getTransactionID());
        $itemsToCancel = null;

        foreach ($items as $item) {
            // count all item which are in cancellation process
            $itemsToCancel += $item->cancelledItems;

            /*
             * Why a continue at the end of a loop
             * if ($item->quantity <= 0) {
                continue;
            }*/
        }

        //only call the logic if there are items to cancel
        if ($itemsToCancel > 0) {
            $operationData['orderId'] = $orderId;
            $operationData['items'] = $items;
            $operationData['subtype'] = 'cancellation';
            $this->_modelFactory->setOrderId($order->getNumber());
            $result = $this->_modelFactory->callPaymentChange($operationData);

            if ($result === true) {
                foreach ($items as $item) {
                    $bind = [
                        'cancelled' => $item->cancelled + $item->cancelledItems
                    ];
                    $this->updateItem($orderId, $item->orderDetailId, $bind);
                    if ($item->cancelledItems <= 0) {
                        continue;
                    }

                    if ($this->Request()->getParam('articleStock') == 1) {
                        $this->_updateArticleStock($item->orderDetailId, $item->cancelledItems);
                    }

                    $this->_history->logHistory($orderId, 'Artikel wurde storniert.', $item->name, $item->articlenumber, $item->cancelledItems);
                }
            }
            $this->setNewOrderState($orderId, 'cancellation');
            $this->View()->assign(
                [
                    'result' => $result,
                    'success' => true
                ]
            );
        } else {
            $this->View()->assign(
                [
                    'success' => false
                ]
            );
        }
    }

    /**
     * returns the given Items and returns the result to the backend
     */
    public function returnItemsAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $items = json_decode($this->Request()->getParam('items'));
        $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        $order = $orderModel->findOneBy(['id' => $orderId]);
        $this->_modelFactory->setTransactionId($order->getTransactionID());
        $itemsToReturn = array_reduce(
            $items,
            function ($sum, $item) {
                return ($sum + $item->returnedItems);
            },
            0
        );


        if ($itemsToReturn < 1) {
            $this->View()->assign(['success' => false]);
            return;
        }


        //only call the logic if there are items to return
        $operationData['orderId'] = $orderId;
        $operationData['items'] = $items;
        $operationData['subtype'] = 'return';
        $this->_modelFactory->setOrderId($order->getNumber());
        $result = $this->_modelFactory->callPaymentChange($operationData);

        if ($result === true) {
            foreach ($items as $item) {
                if ($item->returnedItems <= 0) {
                    continue;
                }

                $bind = [
                    'returned' => $item->returned + $item->returnedItems
                ];
                $this->updateItem($orderId, $item->orderDetailId, $bind);

                if ($this->Request()->getParam('articleStock') == 1) {
                    $this->_updateArticleStock($item->orderDetailId, $item->returnedItems);
                }

                $this->_history->logHistory($orderId, 'Artikel wurde retourniert.', $item->name, $item->articlenumber, $item->returnedItems);
            }
        }

        $this->setNewOrderState($orderId, 'return');

        $this->View()->assign(
            [
                'result' => $result,
                'success' => true
            ]
        );
    }

    /**
     * Add the given Items to the given order
     */
    public function addAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $insertedIds = json_decode($this->Request()->getParam('insertedIds'));
        $subOperation = $this->Request()->getParam('suboperation');
        $order = $this->getModelManager()->find(Order::class, $orderId);

        $qb = $this->getModelManager()->getRepository(\Shopware\Models\Order\Detail::class)->createQueryBuilder('detail');
        $qb->andWhere($qb->expr()->in('detail.id', $insertedIds));
        $addedDetails = $qb->getQuery()->getResult();

        $items = [];
        /** @var \Shopware\Models\Order\Detail $item */
        foreach ($addedDetails as $item) {
            $items[] = [
                'articlename' => $item->getArticleName(),
                'ordernumber' => $item->getArticleNumber(),
                'quantity' => $item->getQuantity(),
                'priceNumeric' => $item->getPrice(),
                'tax_rate' => $order->getNet() ? $item->getTaxRate() : 0,
            ];
        }

        $result = false;
        if(count($items)) {
            $this->_modelFactory->setTransactionId($order->getTransactionId());
            $operationData['orderId'] = $order->getId();
            $operationData['items'] = $items;
            $operationData['subtype'] = 'credit'; //suboperation is always "credit" - also on a debit
            $this->_modelFactory->setOrderId($order->getNumber());

            if ($subOperation === 'debit' && (in_array($order->getPayment()->getName(), ['rpayratepayrate', 'rpayratepayrate0']))) {
                //credit/debit is not allowed for installments
                $result = false;
            } else {
                $result = $this->_modelFactory->callPaymentChange($operationData);
                $bind = null;
                $event = 'Artikel wurde hinzugefügt';
                if ($subOperation === 'credit' || $subOperation === 'debit') {
                    switch ($subOperation) {
                        case 'credit':
                            $event = 'Nachlass wurde hinzugefügt';
                            break;
                        case 'debit':
                            $event = 'Nachbelastung wurde hinzugefügt';
                    }
                    $bind = ['delivered' => 1, 'tax_rate' => 0];
                }

                foreach ($insertedIds as $id) {
                    $newItems = Shopware()->Db()->fetchRow('SELECT * FROM `s_order_details` WHERE `id`=?', [$id]);
                    if ($bind) {
                        $this->updateItem($orderId, $newItems['id'], $bind);
                    }
                    if ($newItems['quantity'] <= 0) {
                        continue;
                    }
                    $this->_history->logHistory($orderId, $event, $newItems['name'], $newItems['articleordernumber'], $newItems['quantity']);
                }

                $this->setNewOrderState($orderId);
            }
        }

        $this->View()->assign(
            [
                'result' => $result,
                'success' => $result
            ]
        );
    }

    /**
     * Updates the given binding for the given article
     *
     * @param string $orderID
     * @param string $articleOrderNumberOrDetailsId
     * @param array  $bind
     */
    private function updateItem($orderID, $articleOrderNumberOrDetailsId, $bind)
    {
        if ($articleOrderNumberOrDetailsId === 'shipping') {
            Shopware()->Db()->update('rpay_ratepay_order_shipping', $bind, '`s_order_id`=' . $orderID);
        } else if ($articleOrderNumberOrDetailsId === 'discount') {
            Shopware()->Db()->update('rpay_ratepay_order_discount', $bind, '`s_order_id`=' . $orderID); //update all discounts
        } else {
            //$positionId = Shopware()->Db()->fetchOne('SELECT `id` FROM `s_order_details` WHERE `orderID`=? AND `articleordernumber`=?', [$orderID, $articleordernumber]);
            Shopware()->Db()->update('rpay_ratepay_order_positions', $bind, '`s_order_details_id`=' . $articleOrderNumberOrDetailsId);
        }
    }

    /**
     * update the stock of an article
     *
     * @param int $orderDetailsId
     * @param int $quantity
     */
    protected function _updateArticleStock($orderDetailsId, $quantity)
    {
        if(is_numeric($orderDetailsId) === false) {
            // id is "shipping" or "discount" - we can't update this ;-)
            return;
        }
        $repo = Shopware()->Models()->getRepository(Detail::class);

        $qb = $repo->createQueryBuilder('detail');
        $qb->innerJoin(OrderDetail::class, 'orderDetail', Join::WITH, 'detail.id = orderDetail.articleDetail')
            ->andWhere($qb->expr()->eq('orderDetail.id', $orderDetailsId))
        ;
        $qb->setMaxResults(1);
        /** @var Detail $detail */
        $detail = $qb->getQuery()->getOneOrNullResult();
        if($detail) {
            $detail->setInStock($detail->getInStock() + $quantity);
            Shopware()->Models()->flush($detail);
        }
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
            [
                'data' => $data[0],
                'success' => true
            ]
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
        $sql = 'SELECT '
            . '`invoice_shipping` AS `price`, '
            . '(1 - `delivered` - `cancelled`) AS `quantityDeliver`, '
            . '(`delivered` - `returned`) AS `quantityReturn`, '
            . '`delivered`, '
            . '`cancelled`, '
            . '`returned`, '
            . '`rpay_ratepay_order_shipping`.`tax_rate` AS `tax_rate`, '
            . '`s_order`.`invoice_shipping_net` '
            . 'FROM `s_order` '
            . 'LEFT JOIN `rpay_ratepay_order_shipping` ON `s_order_id`=`s_order`.`id` '
            . 'LEFT JOIN `s_premium_dispatch` ON `s_order`.`dispatchID`=`s_premium_dispatch`.`id` '
            . 'LEFT JOIN `s_core_tax` ON `s_premium_dispatch`.`tax_calculation`=`s_core_tax`.`id` '
            . 'WHERE `s_order`.`id` = ?';
        $shippingRow = Shopware()->Db()->fetchRow($sql, [$orderId]);
        if (isset($shippingRow['quantityDeliver'])) {
            $shippingRow['quantity'] = 1;
            $shippingRow['articleID'] = 0;
            $shippingRow['orderDetailId'] = 'shipping';
            $shippingRow['name'] = 'shipping';
            $shippingRow['articleordernumber'] = 'shipping';

            return $shippingRow;
        }
    }

    /**
     * Returns the discount as item for the given order
     *
     * @param string $orderId
     *
     * @return array
     */
    private function getDiscountFromDBAsItem($orderId)
    {
        $sql = 'SELECT '
            . '`detail`.`price` AS `price`, '
            . '`detail`.`name` AS `name`, '
            . '(1 - `delivered` - `cancelled`) AS `quantityDeliver`, '
            . '(`delivered` - `returned`) AS `quantityReturn`, '
            . '`delivered`, '
            . '`cancelled`, '
            . '`returned`, '
            . '`position`.`tax_rate` as `tax_rate` '
            . 'FROM `s_order_details` as detail '
            . 'INNER JOIN `rpay_ratepay_order_discount` as position ON `position`.`s_order_detail_id` = `detail`.`id` '
            . 'WHERE `position`.`s_order_id` = ?';
        $rows = Shopware()->Db()->fetchAll($sql, [$orderId]);
        $item = [
            'quantity' => 1,
            'articleID' => 0,
            'orderDetailId' => 'discount',
            'articleordernumber' => 'discount',
            'price' => 0
        ];
        if(count($rows) == 0) {
            return null;
        }

        foreach($rows as $row) {
            $item['price'] += floatval($row['price']);
            $item['name'] .= (isset($item['name']) ? ' & ' : null ) . $row['name'];
        }
        return array_merge($rows[0], $item); // cause any position does have the same delivery, canceled and returned values, we can pick the first row
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
        $sql = 'SELECT '
               . '`articleID`, '
               . 'detail.`id` as orderDetailId, '
               . '`name`, '
               . '`articleordernumber`, '
               . '`price`, '
               . '`quantity`, '
               . '(`quantity` - `delivered` - `cancelled`) AS `quantityDeliver`, '
               . '(`delivered` - `returned`) AS `quantityReturn`, '
               . '`delivered`, '
               . '`cancelled`, '
               . '`returned`, '
               . 'ratepay.`tax_rate` '
               . 'FROM `s_order_details` AS detail '
               . 'INNER JOIN `rpay_ratepay_order_positions` AS ratepay ON detail.`id`=ratepay.`s_order_details_id` '
               . 'WHERE detail.`orderId`=? '
               . 'ORDER BY detail.`id`;';

        $items = Shopware()->Db()->fetchAll($sql, [$orderId]);

        $eventManager = Shopware()->Events();
        foreach($items as $index => $item) {
            $items[$index] = $eventManager->filter('RatePAY_filter_order_items', $item);
        }

        $shipping = $this->getShippingFromDBAsItem($orderId);
        if (!is_null($shipping)) {
            $items[] = $shipping;
        }
        $discount = $this->getDiscountFromDBAsItem($orderId);
        if (!is_null($discount)) {
            $items[] = $discount;
        }

        return $items;
    }

    /**
     * Sets the new Orderstate
     *
     * @param boolean $orderComplete
     */
    private function setNewOrderState($orderId, $operation = null)
    {
        $sql = "SELECT COUNT((`quantity` - `delivered` - `cancelled`)) AS 'itemsLeft' "
               . 'FROM `s_order_details` '
               . 'JOIN `rpay_ratepay_order_positions` ON `s_order_details`.`id` = `rpay_ratepay_order_positions`.`s_order_details_id` '
               . 'WHERE `orderID`=? AND (`quantity` - `delivered` - `cancelled`) > 0';
        try {
            $orderComplete = Shopware()->Db()->fetchOne($sql, [$orderId]);

            if ($operation === 'cancellation') {
                $newState = $orderComplete == 0 ? $this->_config['RatePayPartialCancellation'] : $this->_config['RatePayFullCancellation'];
            } elseif ($operation === 'delivery') {
                //only set if order is not partial returned / cancelled
                if ($orderComplete != $this->_config['RatePayPartialReturn'] && $orderComplete != $this->_config['RatePayPartialCancellation']) {
                    $newState = $orderComplete == 0 ? $this->_config['RatePayFullDelivery'] : $this->_config['RatePayPartialDelivery'];
                }
            } elseif ($operation === 'return') {
                $newState = $orderComplete == 0 ? $this->_config['RatePayFullReturn'] : $this->_config['RatePayFullCancellation'];
            }

            // return if no status update
            if (null === $newState) {
                return;
            }

            Shopware()->Db()->update('s_order', [
                'status' => $newState
            ], '`id`=' . $orderId);
        } catch (\Exception $exception) {
            Logger::singleton()->error($exception->getMessage());
        }
    }

    /**
     * @param $articleOrderNumbers
     * @param $orderId
     * @return bool
     */
    private function insertNewPositionsToOrder($articleOrderNumbers, $orderId)
    {
        $articleBinds = array_map(function ($item) {
            return '?';
        }, $articleOrderNumbers);
        $orderDetailIdsQuery = 'SELECT `id` FROM `s_order_details`
          WHERE `orderID`=? AND `articleordernumber` IN (' . join(', ', $articleBinds) . ')';

        try {
            $queryArguments = array_merge([$orderId], $articleOrderNumbers);
            $orderDetailIds = array_map(
                function ($item) {
                    return $item['id'];
                },
                Shopware()->Db()->fetchAll($orderDetailIdsQuery, $queryArguments)
            );
            $newValues = '(' . implode('), (', $orderDetailIds) . ')';
            $sqlInsert = 'INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES ' . $newValues;
            Shopware()->Db()->query($sqlInsert);
        } catch (\Exception $exception) {
            Logger::singleton()->error($exception->getMessage() . '. SQL:' . $sqlInsert);
            return false;
        }

        return true;
    }
}
