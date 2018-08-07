<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 16.07.18
 * Time: 11:21
 */

namespace RpayRatePay\Component\Service;

use RpayRatePay\Component\Mapper\PaymentRequestData;
use Shopware\Components\Plugin;

class PaymentProcessor
{
    const PAYMENT_STATUS_COMPLETELY_PAID = 12;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function initShipping($order)
    {
        $this->db->query(
            "INSERT INTO `rpay_ratepay_order_shipping` (`s_order_id`) VALUES(?)",
            array($order->getId())
        );
    }

    /**
     * @param $order
     * @param $paymentRequestResult
     * @param $fallbackShipping
     */
    public function setOrderAttributes($order, $paymentRequestResult, $fallbackShipping, $backend = false)
    {
        $this->db->update( //wird wohl nicht gehen, da das Custom-Feld nicht da ist
            's_order_attributes',
            array(
                'attribute5' => $paymentRequestResult->getDescriptor(),
                'attribute6' => $paymentRequestResult->getTransactionId(),
                'ratepay_fallback_shipping' => $fallbackShipping,
                'ratepay_backend' => $backend,
            ),
            'orderID=' . $order->getId()
        );
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    public function insertRatepayPositions($order)
    {
        $sql = "SELECT `id` FROM `s_order_details` WHERE `ordernumber`=?;";
        Shopware()->Pluginlogger()->info('NOW SETTING ORDER DETAILS: ' . $sql);

        $rows = $this->db->fetchAll($sql, array($order->getNumber()));

        Shopware()->Pluginlogger()->info('GOT ROWS ' . count($rows));

        $values = "";
        foreach ($rows as $row) {
            $values .= "(" . $row['id'] . "),";
        }
        $values = substr($values, 0, -1);
        $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES " . $values;
        Shopware()->Pluginlogger()->info('INSERT NOW ' . $sqlInsert);
        try {
            $this->db->query($sqlInsert);
        } catch (Exception $exception) {
            Shopware()->Pluginlogger()->error($exception->getMessage());
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
    public function setPaymentStatusPaid($order)
    {
        //set cleared date
        $dateTime = new \DateTime();

        $order->setClearedDate($dateTime);

        Shopware()->Models()->flush($order);

        Shopware()->Modules()->Order()
            ->setPaymentStatus($order->getId(),
                self::PAYMENT_STATUS_COMPLETELY_PAID,
                false
            );
    }

    /**
     * @param $transactionId
     * @param \Shopware\Models\Order\Order $order
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