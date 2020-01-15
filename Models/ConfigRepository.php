<?php

namespace RpayRatePay\Models;

use RpayRatePay\Models\ProfileConfig;
use Shopware\Components\Model\ModelRepository;

class ConfigRepository extends ModelRepository
{
    /**
     * @param int $shopId
     * @param string $countryCode
     * @param boolean $isZeroInstallment
     * @param boolean $isBackend
     * @return ProfileConfig|null
     */
    public function findConfiguration($shopId, $countryCode, $isZeroInstallment, $isBackend) {
        $qb = $this->createQueryBuilder('config');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('config.countryCodeBilling', ':country_code'),
                $qb->expr()->eq('config.shopId', ':shop_id'),
                $qb->expr()->eq('config.backend', ':backend'),
                $qb->expr()->eq('config.isZeroPercentInstallment', ':is_zero_percent_installment')
            )
        );
        $qb->setParameter('country_code', $countryCode);
        $qb->setParameter('shop_id', $shopId);
        $qb->setParameter('backend', $isBackend);
        $qb->setParameter('is_zero_percent_installment', $isZeroInstallment);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
