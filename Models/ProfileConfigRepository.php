<?php


namespace RpayRatePay\Models;


use Shopware\Components\Model\ModelRepository;

class ProfileConfigRepository extends ModelRepository
{

    /**
     * @param int $shopId
     * @param bool $backend
     * @return ProfileConfig|null
     */
    public function findOneByShop($shopId, $backend = false) {
        return $this->findOneBy(['shopId' => $shopId, 'backend' => $backend]);
    }

    /**
     * @param string $profileId
     * @param int $shopId
     * @return ProfileConfig|null
     */
    public function findOneByShopAndProfileId($profileId, $shopId)
    {
        return $this->findOneBy(['shopId' => $shopId, 'profileId' => $profileId]);
    }

}
