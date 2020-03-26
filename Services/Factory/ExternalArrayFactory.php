<?php


namespace RpayRatePay\Services\Factory;


use Shopware\Models\Order\Order;

class ExternalArrayFactory
{

    const ARRAY_KEY = 'External';

    public function getData(Order $order)
    {
        if ($order->getTrackingCode()) {
            $data = [];
            $data['Tracking']['Id'] = $order->getTrackingCode();
            if ($order->getDispatch()) {
                $supportedMethods = ['DHL', 'DPD', 'GLS', 'HLG', 'HVS', 'OTH', 'TNT', 'UPS'];
                foreach ($supportedMethods as $supportedMethod) {
                    if (strpos($order->getDispatch()->getName(), $supportedMethod) === 0) {
                        $data['Tracking']['Provider'] = $supportedMethod;
                        break;
                    }
                }
            }
            return $data;
        } else {
            return null;
        }
    }
}
