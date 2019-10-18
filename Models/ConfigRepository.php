<?php


namespace Shopware\Plugins\Community\Frontend\RpayRatePay\Models;


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
                $qb->expr()->eq('config.country', ':country_code'),
                $qb->expr()->eq('config.shopId', ':shop_id'),
                $qb->expr()->eq('config.backend', ':backend')
            )
        );
        $qb->setParameter('country_code', $countryCode);
        $qb->setParameter('shop_id', $shopId);
        $qb->setParameter('backend', $isBackend);

        if($isZeroInstallment) {
            $qb->andWhere(
                $qb->expr()->like('config.profileId', ':installment_profile_id_suffix'),
                $qb->expr()->isNotNull('config.installment0')
            );
        } else {
            $qb->andWhere(
                $qb->expr()->notLike('config.profileId', ':installment_profile_id_suffix')
            );
        }
        $qb->setParameter('installment_profile_id_suffix', '%_0RT');
        return $qb->getQuery()->getOneOrNullResult();
    }

}
