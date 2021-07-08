<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


use RpayRatePay\Services\Config\ConfigService;

class FeatureService
{

    protected $enabledFlags = [];

    public function __construct(ConfigService $configService)
    {
        $this->enabledFlags = $configService->getEnabledFeatures();
    }

    public function isFeatureEnabled($featureKey)
    {
        return in_array($featureKey, $this->enabledFlags, true);
    }

}
