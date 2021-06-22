<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Factory;


use RpayRatePay\Services\Config\ConfigService;
use Shopware\Models\Order\Order;

class ExternalArrayFactory
{

    /**
     * @var \RpayRatePay\Services\Config\ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    const ARRAY_KEY = 'External';

    public function getData(Order $order)
    {
        if ($plainTrackingCode = $order->getTrackingCode()) {
            $provider = null;
            if ($order->getDispatch()) {
                $supportedMethods = ['DHL', 'DPD', 'GLS', 'HLG', 'HVS', 'OTH', 'TNT', 'UPS'];
                foreach ($supportedMethods as $supportedMethod) {
                    if (strpos($order->getDispatch()->getName(), $supportedMethod) === 0) {
                        $provider = $supportedMethod;
                        break;
                    }
                }
            }

            if ($separator = $this->configService->getTrackingCodeSeparator()) {
                $trackingCodes = explode($separator, $plainTrackingCode);
            } else {
                $trackingCodes = [$plainTrackingCode];
            }

            $data = [];
            foreach ($trackingCodes as $i => $code) {
                $data['Tracking'][$i]['Id']['Description'] = trim($code);
                $data['Tracking'][$i]['Id']['Provider'] = $provider;
            }

            return $data;
        }
        return null;
    }
}
