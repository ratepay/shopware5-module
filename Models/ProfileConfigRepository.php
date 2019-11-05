<?php


namespace RpayRatePay\Models;


use Shopware\Components\Model\ModelRepository;

class ProfileConfigRepository extends ModelRepository
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
                $qb->expr()->isNotNull('config.installment0Config')
            );
        } else {
            $qb->andWhere(
                $qb->expr()->isNull('config.installment0Config')
            );
        }
        return $qb->getQuery()->getOneOrNullResult();
    }

}
