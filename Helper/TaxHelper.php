<?php


namespace RpayRatePay\Helper;


use RpayRatePay\Component\Mapper\PaymentRequestData;
use Shopware\Models\Order\Detail as OrderDetail;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class TaxHelper
{
    /**
     * @param Order|PaymentRequestData $order
     * @param OrderDetail|PositionStruct $item
     * @return float
     */
    public static function getItemGrossPrice($order, $item)
    {
        if ($item instanceof PositionStruct) {
            $price = $item->getPrice();
        } else {
            $price = $item->getPrice();
        }
        if ($order instanceof Order) {
            if ($order->getTaxFree() == 1) {
                // no tax got charged
                return $price;
            } else if ($order->getNet()) {
                // show prices without tax, but tax is included
                return round($price * (1 + ($item->getTaxRate() / 100)), 2);
            } else {
                // no change
                return $price;
            }
        }
        return $price;
    }

    /**
     * @param Order|PaymentRequestData $order
     * @param OrderDetail|PositionStruct $item
     * @return float|int
     */
    public static function getItemTaxRate($order, $item)
    {
        if ($order instanceof Order) {
            return $order->getNet() == 1 && $order->getTaxFree() == 1 ? 0 : $item->getTaxRate();
        }
        return $item->getTaxRate();
    }

    /**
     * @param Order|PaymentRequestData $order
     * @return float
     */
    public static function getShippingGrossPrice($order)
    {
        if ($order instanceof Order) {
            return $order->getInvoiceShipping();
        } else if ($order instanceof PaymentRequestData) {
            return $order->getShippingCost();
        }
        return null;
    }

    /**
     * @param Order|PaymentRequestData|OrderStruct $order
     * @return float|int
     */
    public static function getShippingTaxRate($order)
    {
        if ($order instanceof Order) {
            $calculatedShippingTaxRate = self::taxFromPrices($order->getInvoiceShippingNet(), $order->getInvoiceShipping());
            return $calculatedShippingTaxRate > 0 ? $order->getInvoiceShippingTaxRate() : 0;
        } else if ($order instanceof OrderStruct) {
            return $order->getShippingCostsTaxRate();
        } else if ($order instanceof PaymentRequestData) {
            return $order->getShippingTax();
        }
        return null;
    }

    /**
     * @param float|int $netPrice
     * @param float|int $grossPrice
     * @return float|int
     */
    public static function taxFromPrices($netPrice, $grossPrice)
    {
        $tax = ($grossPrice - $netPrice) / $netPrice;
        return $tax * 100;
    }

}
