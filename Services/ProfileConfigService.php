<?php

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
