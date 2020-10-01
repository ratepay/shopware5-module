<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use RatePAY\Model\Response\AbstractResponse;
use RpayRatePay\Component\History;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Request\AbstractAddRequest;
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
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var PaymentDeliverService
     */
    protected $paymentDeliverService;
    /**
     * @var PaymentCancelService
     */
    private $paymentCancelService;
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
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;


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
        $this->snippetManager = $this->container->get('snippets');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $orderId = $this->Request()->getParam('orderId');
        if (null == $orderId) {
            throw new Exception('RatepayOrderDetail controller requires parameter orderId');
        }
    }

    /**
     * Loads the History for the given Order
     */
    public function loadHistoryStoreAction()
    {
        //TODO DQL & Models + update store in ExtJs to use own controller
        $orderId = $this->Request()->getParam('orderId');
        $sql = 'SELECT * FROM `rpay_ratepay_order_history`'
            . ' WHERE `orderId`=? '
            . 'ORDER BY `id` DESC';
        $history = Shopware()->Db()->fetchAll($sql, [$orderId]);

        $this->View()->assign(
            [
                'data' => $history,
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
            . '`detail`.`id` AS `orderDetailId`, '
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
        if($discounts = $this->getDiscountFromDBAsItem($orderId)) {
            $data = array_merge($data, $discounts);
        }
        return $data;
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
            . '`detail`.`id` AS `orderDetailId`, '
            . '`detail`.`price` AS `price`, '
            . '`detail`.`name` AS `name`, '
            . '`detail`.`quantity` AS `quantity`,'
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
        if (count($rows) == 0) {
            return null;
        }

        return $rows;
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
                $position = new BasketPosition($item->articlenumber, $item->deliveredItems);
                $detail = $item->orderDetailId ? $this->modelManager->find(Detail::class, $item->orderDetailId) : null;
                if ($detail) {
                    $position->setOrderDetail($detail);
                }
                $items[$position->getProductNumber()] = $position;
            }
        }
        $isSuccess = false;
        if (count($items) > 0) {

            try {
                $this->paymentDeliverService->setItems($items);
                $this->paymentDeliverService->setOrder($order);
                /** @var AbstractResponse $response */
                $response = $this->paymentDeliverService->doRequest();
                $isSuccess = $response === true || $response->isSuccessful();
                if ($isSuccess) {
                    $this->View()->assign('message', $this->getSnippet('backend/ratepay/messages', 'DeliverySuccessful'));
                } else {
                    $this->View()->assign('message', $response->getReasonMessage());
                }
            } catch (Exception $e) {
                $this->View()->assign('message', $e->getMessage());
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
                $position = new BasketPosition($item->articlenumber, $item->cancelledItems);
                $detail = $item->orderDetailId ? $this->modelManager->find(Detail::class, $item->orderDetailId) : null;
                if ($detail) {
                    $position->setOrderDetail($detail);
                }
                $items[$position->getProductNumber()] = $position;
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
                $this->View()->assign('message', $this->getSnippet('backend/ratepay/messages', 'CancelSuccessful'));
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
                $position = new BasketPosition($item->articlenumber, $item->returnedItems);
                $detail = $item->orderDetailId ? $this->modelManager->find(Detail::class, $item->orderDetailId) : null;
                if ($detail) {
                    $position->setOrderDetail($detail);
                }
                $items[$position->getProductNumber()] = $position;
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
                $this->View()->assign('message', $this->getSnippet('backend/ratepay/messages', 'ReturnSuccessful'));
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
        $addedDetailIds = json_decode($this->Request()->getParam('detailIds'));
        $operationType = $this->Request()->getParam('operationType');

        $order = $this->modelManager->find(Order::class, $orderId);

        $qb = $this->modelManager->getRepository(Detail::class)->createQueryBuilder('detail');
        $qb->andWhere($qb->expr()->in('detail.id', $addedDetailIds));
        /** @var Detail[] $addedDetails */
        $addedDetails = $qb->getQuery()->getResult();

        $isSuccess = true;
        if (count($addedDetails)) {
            $basketArrayBuilder = new BasketArrayBuilder($order);
            foreach ($addedDetails as $detail) {
                if ($detail->getPrice() > 0 && PaymentMethods::isInstallment($order->getPayment())) {
                    $this->View()->assign([
                        'message' => $this->getSnippet('backend/ratepay/messages', 'CannotAddProductToRatePayment'),
                        'result' => false,
                        'success' => false
                    ]);
                    return;
                }
                $basketArrayBuilder->addItem($detail);
            }

            /** @var AbstractAddRequest $service */
            $service = null;

            switch ($operationType) {
                case 'debit':
                    $service = $this->paymentDebitService;
                    break;
                case 'credit':
                    $service = $this->paymentCreditService;
                    break;
                default:
                    throw new RuntimeException($this->getSnippet('backend/ratepay/messages', 'UnknownOperation'));
            }

            $service->setOrder($order);
            $service->setItems($basketArrayBuilder);
            $response = $service->doRequest();

            $isSuccess = $response === true || $response->isSuccessful();
            if ($isSuccess) {
                $this->View()->assign('message', $this->getSnippet('backend/ratepay/messages', ucfirst($operationType).'Successful'));
            } else {
                $this->View()->assign('message', $response->getReasonMessage());
            }
        }
        $this->View()->assign([
            'result' => $isSuccess,
            'success' => $isSuccess
        ]);
    }

    protected function getSnippet($namespace, $key)
    {
        return $this->snippetManager->getNamespace($namespace)->get($key);
    }
}
