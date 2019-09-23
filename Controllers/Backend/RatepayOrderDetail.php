<?php

use Monolog\Logger;
use RatePAY\Model\Response\AbstractResponse;
use RpayRatePay\Component\History;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Component\Mapper\ModelFactory;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Request\PaymentCancelService;
use RpayRatePay\Services\Request\PaymentCreditService;
use RpayRatePay\Services\Request\PaymentDebitService;
use RpayRatePay\Services\Request\PaymentDeliverService;
use RpayRatePay\Services\Request\PaymentReturnService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
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
class Shopware_Controllers_Backend_RatepayOrderDetail extends Shopware_Controllers_Backend_ExtJs
{
    private $_config;

    /** @var ModelFactory */
    private $_modelFactory;
    private $_service;
    private $_history;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var PaymentCancelService
     */
    private $paymentCancelService;
    /**
     * @var PaymentDeliverService
     */
    protected $paymentDeliverService;
    /**
     * @var PaymentReturnService
     */
    private $paymentReturnService;
    /**
     * @var object|PaymentDebitService
     */
    private $paymentDebitService;
    /**
     * @var PaymentCreditService
     */
    private $paymentCreditService;


    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->modelManager = $this->container->get('models');
        $this->paymentDeliverService = $this->container->get(PaymentDeliverService::class);
        $this->paymentCancelService = $this->container->get(PaymentCancelService::class);
        $this->paymentReturnService = $this->container->get(PaymentReturnService::class);
        $this->paymentDebitService = $this->container->get(PaymentDebitService::class);
        $this->paymentCreditService = $this->container->get(PaymentCreditService::class);
        $this->logger = $this->container->get('rpay_rate_pay.logger');
    }

    /**
     * index action is called if no other action is triggered
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {

    }

    public function preDispatch()
    {
        parent::preDispatch();

        //set correct subshop for backend processes
        $orderId = $this->Request()->getParam('orderId');
        if (null !== $orderId) {
            $order = $this->modelManager->find(Order::class, $orderId);

            $attributes = $order->getAttribute();
            $backend = (bool)($attributes->getRatepayBackend());
            $netPrices = $order->getNet() === 1;
            $this->_modelFactory = new ModelFactory($this->_config, $backend, $netPrices);
        } else {
            throw new Exception('RatepayOrderDetail controller requires parameter orderId');
            //$this->_modelFactory = new ModelFactory();
        }
        $this->_history = new History();
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
            } catch (Exception $exception) {
                $this->logger->warn('Unable to initialize order position ' . $articleNumber . '. ' . $exception->getMessage());
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
        $history = new History();
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
        $itemsParam = json_decode($this->Request()->getParam('items'));
        $order = $this->modelManager
            ->getRepository(Order::class)
            ->findOneBy(['id' => $orderId]);


        $items = [];
        foreach ($itemsParam as $item) {
            if ($item->deliveredItems > 0) {
                $items[$item->articlenumber] = $item->deliveredItems;
            }
        }
        $isSuccess = true;
        if (count($items) > 0) {

            $this->paymentDeliverService->setItems($items);
            $this->paymentDeliverService->setOrder($order);
            /** @var AbstractResponse $response */
            $response = $this->paymentDeliverService->doRequest();
            $isSuccess = $response === true || $response->isSuccessful();
            if ($isSuccess) {
                $this->setNewOrderState($orderId, 'delivery');
            } else {
                $this->View()->assign('message', $response->getReasonMessage());
            }
        }
        $this->View()->assign([
            'result' => $isSuccess,
            'success' => true
        ]);
    }

    /**
     * Cancel the given Items and returns the result to the backend
     */
    public function cancelItemsAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $itemsParam = json_decode($this->Request()->getParam('items'));

        $items = [];
        foreach ($itemsParam as $item) {
            if ($item->cancelledItems > 0) {
                $items[$item->articlenumber] = $item->cancelledItems;
            }
        }

        $isSuccess = true;
        if (count($items) > 0) {
            $this->paymentCancelService->setOrder($orderId);
            $this->paymentCancelService->setItems($items);
            $this->paymentCancelService->setUpdateStock($this->Request()->getParam('articleStock') == 1);
            /** @var AbstractResponse $response */
            $response = $this->paymentCancelService->doRequest();
            $isSuccess = $response === true || $response->isSuccessful();
            if ($isSuccess) {
                $this->setNewOrderState($orderId, 'cancellation');
            } else {
                $this->View()->assign('message', $response->getReasonMessage());
            }
        }
        $this->View()->assign([
            'result' => $isSuccess,
            'success' => true
        ]);
    }

    /**
     * returns the given Items and returns the result to the backend
     */
    public function returnItemsAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $itemsParam = json_decode($this->Request()->getParam('items'));

        $items = [];
        foreach ($itemsParam as $item) {
            if ($item->returnedItems > 0) {
                $items[$item->articlenumber] = $item->returnedItems;
            }
        }

        $isSuccess = true;
        if (count($items) > 0) {
            $this->paymentReturnService->setOrder($orderId);
            $this->paymentReturnService->setItems($items);
            $this->paymentReturnService->setUpdateStock($this->Request()->getParam('articleStock') == 1);
            /** @var AbstractResponse $response */
            $response = $this->paymentReturnService->doRequest();
            $isSuccess = $response === true || $response->isSuccessful();
            if ($isSuccess) {
                $this->setNewOrderState($orderId, 'return');
            } else {
                $this->View()->assign('message', $response->getReasonMessage());
            }
        }
        $this->View()->assign([
            'result' => $isSuccess,
            'success' => true
        ]);
    }

    /**
     * Add the given Items to the given order
     */
    public function addAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $addedDetailIds = json_decode($this->Request()->getParam('insertedIds'));
        $subOperation = $this->Request()->getParam('suboperation');

        $order = $this->modelManager->find(Order::class, $orderId);

        if (PaymentMethods::isInstallment($order->getPayment())) {
            $this->View()->assign([
                'message' => 'Einer Bestellung mit der Zahlart Ratenzahlung/Finanzierung kann kein Artikel automatisch hinzugefügt werden.',
                'result' => false,
                'success' => true
            ]);
            return;
        }

        $qb = $this->modelManager->getRepository(Detail::class)->createQueryBuilder('detail');
        $qb->andWhere($qb->expr()->in('detail.id', $addedDetailIds));
        $addedDetails = $qb->getQuery()->getResult();

        $isSuccess = true;
        if (count($addedDetails)) {
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($addedDetails as $detail) {
                $basketArrayBuilder->addItem($detail);
            }

            switch ($subOperation) {
                case 'debit':
                    $service = $this->paymentDebitService;
                    break;
                case 'credit':
                    $service = $this->paymentCreditService;
                    break;
                default:
                    throw new RuntimeException('unknown operation');
            }

            $service->setOrder($order);
            $service->setItems($basketArrayBuilder);
            $response = $service->doRequest();

            $isSuccess = $response === true || $response->isSuccessful();

            $this->setNewOrderState($orderId);
        }
        $this->View()->assign([
            'result' => $isSuccess,
            'success' => true
        ]);
    }


    /**
     * Returns the article for the given id
     */
    public function getArticleAction()
    {
        $id = $this->Request()->getParam('id');
        $data = $this->modelManager->getRepository('Shopware\Models\Article\Article')->getArticleBaseDataQuery($id)->getArrayResult();
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
     * @param float $tax
     *
     * @return float
     */
    protected function getPrices($id, $tax)
    {
        $prices = $this->modelManager->getRepository('Shopware\Models\Article\Article')
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
        //TODO DQL/Models
        $sql = 'SELECT '
            . '`invoice_shipping` AS `price`, '
            . '(1 - `delivered` - `cancelled`) AS `quantityDeliver`, '
            . '(`delivered` - `returned`) AS `quantityReturn`, '
            . '`delivered`, '
            . '`cancelled`, '
            . '`returned`, '
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
        //TODO DQL/Models
        $sql = 'SELECT '
            . '`detail`.`price` AS `price`, '
            . '`detail`.`name` AS `name`, '
            . '(1 - `delivered` - `cancelled`) AS `quantityDeliver`, '
            . '(`delivered` - `returned`) AS `quantityReturn`, '
            . '`detail`.`articleordernumber` as `articleordernumber`, '
            . '`delivered`, '
            . '`cancelled`, '
            . '`returned`'
            . 'FROM `s_order_details` as detail '
            . 'INNER JOIN `rpay_ratepay_order_discount` as position ON `position`.`s_order_details_id` = `detail`.`id` '
            . 'WHERE `detail`.`orderID` = ?';
        $rows = Shopware()->Db()->fetchAll($sql, [$orderId]);
        $item = [
            'quantity' => 1,
            'articleID' => 0,
            //'articleordernumber' => 'discount',
            'price' => 0
        ];
        if (count($rows) == 0) {
            return null;
        }

        foreach ($rows as $row) {
            $item['price'] += floatval($row['price']);
            $item['name'] .= (isset($item['name']) ? ' & ' : null) . $row['name'];
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
        //TODO DQL/Models
        $sql = 'SELECT '
            . '`articleID`, '
            . '`name`, '
            . '`articleordernumber`, '
            . '`price`, '
            . '`quantity`, '
            . '(`quantity` - `delivered` - `cancelled`) AS `quantityDeliver`, '
            . '(`delivered` - `returned`) AS `quantityReturn`, '
            . '`delivered`, '
            . '`cancelled`, '
            . '`returned`'
            . 'FROM `s_order_details` AS detail '
            . 'INNER JOIN `rpay_ratepay_order_positions` AS ratepay ON detail.`id`=ratepay.`s_order_details_id` '
            . 'WHERE detail.`orderId`=? '
            . 'ORDER BY detail.`id`;';

        $data = Shopware()->Db()->fetchAll($sql, [$orderId]);
        $shipping = $this->getShippingFromDBAsItem($orderId);
        if (!is_null($shipping)) {
            $data[] = $shipping;
        }
        $discount = $this->getDiscountFromDBAsItem($orderId);
        if (!is_null($discount)) {
            $data[] = $discount;
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
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
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
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . '. SQL:' . $sqlInsert);
            return false;
        }

        return true;
    }
}
