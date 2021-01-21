<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;


use RpayRatePay\DTO\PaymentConfigSearch;
use Shopware\Components\Model\ModelRepository;

class PaymentConfigRepository extends ModelRepository
{

    /**
     * @param PaymentConfigSearch $configSearch
     * @return ConfigPayment|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findPaymentMethodConfiguration(PaymentConfigSearch $configSearch)
    {
        $qb = $this->createQueryBuilder('payment_config');
        $qb->join('payment_config.profileConfig', 'profile_config')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->like('profile_config.countryCodesBilling', ':billing_country_code'), // not so beautiful
                    $qb->expr()->like('profile_config.countryCodesDelivery', ':shipping_country_code'), // not so beautiful
                    $qb->expr()->like('profile_config.currencies', ':currency'), // not so beautiful
                    $qb->expr()->eq('profile_config.shopId', ':shop_id'),
                    $qb->expr()->eq('profile_config.backend', ':backend'),
                    $qb->expr()->eq('profile_config.active', true),
                    $qb->expr()->eq('payment_config.paymentMethod', ':payment_method_id')
                )
            )
            ->setMaxResults(1);

        $qb->setParameter('billing_country_code', '%'.$configSearch->getBillingCountry().'%');
        $qb->setParameter('shipping_country_code', '%'.$configSearch->getShippingCountry().'%');
        $qb->setParameter('currency', '%'.$configSearch->getCurrency().'%');
        $qb->setParameter('shop_id', $configSearch->getShop());
        $qb->setParameter('backend', $configSearch->isBackend());
        $qb->setParameter('payment_method_id', $configSearch->getPaymentMethod());

        return $qb->getQuery()->getOneOrNullResult();
    }
}
