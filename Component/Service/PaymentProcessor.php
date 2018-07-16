<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 16.07.18
 * Time: 11:21
 */

namespace RpayRatePay\Component\Service;

use RpayRatePay\Component\Mapper\PaymentRequestData;

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
    public function setOrderAttributes($order, $paymentRequestResult, $fallbackShipping)
    {
        $this->db->update( //wird wohl nicht gehen, da das Custom-Feld nicht da ist
            's_order_attributes',
            array(
                'attribute5' => $paymentRequestResult->getDescriptor(),
                'attribute6' => $paymentRequestResult->getTransactionId(),
                'ratepay_fallback_shipping' => $fallbackShipping,
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
        $rows = $this->db->fetchAll($sql, array($order->getNumber()));
        $values = "";
        foreach ($rows as $row) {
            $values .= "(" . $row['id'] . "),";
        }
        $values = substr($values, 0, -1);
        $sqlInsert = "INSERT INTO `rpay_ratepay_order_positions` (`s_order_details_id`) VALUES " . $values;
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
        $countryCode = PaymentRequestData::findCountryISO($order->getBilling());
        $modelFactory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend);
        $modelFactory->setTransactionId($transactionId);
        $modelFactory->setOrderId($order->getNumber());
        $modelFactory->callPaymentConfirm($countryCode);
    }

}