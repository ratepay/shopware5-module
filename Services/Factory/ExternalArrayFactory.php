<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        }
        return null;
    }
}
