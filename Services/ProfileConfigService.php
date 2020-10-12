<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;

use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Models\ConfigRepository;

class ProfileConfigService
{
    /**
     * @param string$countryCode
     * @param int $shopId
     * @param boolean $isZeroPercentInstallment
     * @param boolean $isBackend
     * @return ProfileConfig|null
     */
    public static function getProfileConfig($countryCode, $shopId, $isZeroPercentInstallment, $isBackend)
    {

        /** @var ConfigRepository $repo */
        $repo = Shopware()->Models()->getRepository(ProfileConfig::class);
        return $repo->findConfiguration($shopId, $countryCode, $isZeroPercentInstallment, $isBackend);
    }
}
