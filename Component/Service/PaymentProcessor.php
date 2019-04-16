<?php

namespace RpayRatePay\Component\Service;

use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\ShopwareUtil;
use Shopware\Models\Order\Detail;

class PaymentProcessor
{
    private $db;

    /**
     * @var ConfigLoader
     */
    protected $configLoader;

    public function __construct(
        $db,
        ConfigLoader $configLoader
    )
    {
        $this->db = $db;
        $this->configLoader = $configLoader;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function initShipping($order)
    {
        $this->db->query(
            'INSERT INTO `rpay_ratepay_order_shipping` (`s_order_id`) VALUES(?)',
            [$order->getId()]
        );
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function initDiscount($order)
    {
        if($this->configLoader->commitDiscountAsCartItem() == false) {
            /** @var Detail $detail */
            foreach ($order->getDetails() as $detail) {
                if (
                    $detail->getMode() != 0 && // no products
                    ($detail->getMode() != 4 || $detail->getPrice() < 0) // no positive surcharges
                ) {
                    $this->db->query(
                        'INSERT INTO `rpay_ratepay_order_discount` (`s_order_id`, `s_order_detail_id`) VALUES(?, ?)',
                        [$order->getId(), $detail->getId()]
                    );
                }
            }
        }
    }

    /**
     * @param $order
     * @param $paymentRequestResult
     * @param $fallbackShipping
     * @param $fallbackDiscount
     * @param bool $backend
     */
    public function setOrderAttributes($order, $paymentRequestResult, $fallbackShipping, $fallbackDiscount, $backend = false)
    {
        $this->db->update( //wird wohl nicht gehen, da das Custom-Feld nicht da ist
            's_order_attributes',
            [
                'attribute5' => $paymentRequestResult->getDescriptor(),
                'attribute6' => $paymentRequestResult->getTransactionId(),
                'ratepay_fallback_shipping' => $fallbackShipping,
                'ratepay_fallback_discount' => $fallbackDiscount,
                'ratepay_backend' => $backend,
            ],
            'orderID=' . $order->getId()
        );
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function insertRatepayPositions($order)
    {
        $sql = 'SELECT `id`, `modus`, `price` FROM `s_order_details` WHERE `ordernumber`=?;';
        Logger::singleton()->info('NOW SETTING ORDER DETAILS: ' . $sql);

        $rows = $this->db->fetchAll($sql, [$order->getNumber()]);

        Logger::singleton()->info('GOT ROWS ' . count($rows));

        $commitDiscountAsCartItem = $this->configLoader->commitDiscountAsCartItem();
        $values = '';
        foreach ($rows as $row) {

            if($row['modus'] != 0 && // not a product
                ($row['modus'] != 4 || $row['price'] < 0) && // not a positive surcharge
                $commitDiscountAsCartItem == false
            ) {
                continue; //this position will be written into the `rpay_ratepay_order_discount` table
            }
            $values .= '(' . $row['id'] . '),';
        }
        $values = substr($values, 0, -1);
        $sqlInsert = 'INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES ' . $values;
        Logger::singleton()->info('INSERT NOW ' . $sqlInsert);
        try {
            $this->db->query($sqlInsert);
        } catch (\Exception $exception) {
            Logger::singleton()->error($exception->getMessage());
        }
    }

    public function setOrderTransactionId($order, $transactionId)
    {
        $order->setTransactionId($transactionId);
        Shopware()->Models()->flush($order);
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function setPaymentStatus($order)
    {
        //set cleared date
        $dateTime = new \DateTime();

        $order->setClearedDate($dateTime);
        Shopware()->Models()->flush($order);

        Shopware()->Modules()->Order()
            ->setPaymentStatus(
                $order->getId(),
                ShopwareUtil::getStatusAfterRatePayPayment($order->getPayment()),
                false
            );
    }

    /**
     * @param $transactionId
     * @param \Shopware\Models\Order\Order $order
     * @param bool $backend
     * @throws \Exception
     */
    public function sendPaymentConfirm($transactionId, $order, $backend = false)
    {
        $netPrices = $order->getNet() === 1;
        $countryCode = PaymentRequestData::findCountryISO($order->getBilling());
        $modelFactory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, $netPrices);
        $modelFactory->setTransactionId($transactionId);
        $modelFactory->setOrderId($order->getNumber());
        $modelFactory->callPaymentConfirm($countryCode);
    }


}
