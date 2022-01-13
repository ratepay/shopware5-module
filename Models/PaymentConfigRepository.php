<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;


use InvalidArgumentException;
use RpayRatePay\DTO\PaymentConfigSearch;
use Shopware\Components\Model\ModelRepository;

class PaymentConfigRepository extends ModelRepository
{

    /**
     * @param PaymentConfigSearch $configSearch
     * @return ConfigPayment|null
     */
    public function findPaymentMethodConfiguration(PaymentConfigSearch $configSearch)
    {
        $qb = $this->getFindPaymentMethodConfigurationsQueryBuilder($configSearch);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param PaymentConfigSearch $configSearch
     * @return ConfigPayment[]
     */
    public function findPaymentMethodConfigurations(PaymentConfigSearch $configSearch)
    {
        $qb = $this->getFindPaymentMethodConfigurationsQueryBuilder($configSearch);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \RpayRatePay\DTO\PaymentConfigSearch $configSearch
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getFindPaymentMethodConfigurationsQueryBuilder(PaymentConfigSearch $configSearch)
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
            );

        if ($configSearch->isB2b() === true) {
            // config must support B2B orders
            $qb->andWhere($qb->expr()->eq('payment_config.allowB2b', true));
        }

        if ($configSearch->isNeedsAllowDifferentAddress() === true) {
            // config must support different addresses
            $qb->andWhere($qb->expr()->eq('payment_config.allowDifferentAddresses', true));
        }

        if ($configSearch->getTotalAmount() !== null) {
            // config must match min/max total amount
            if ($configSearch->isB2b() === null) {
                throw new InvalidArgumentException(
                    'if you want to filter for totalAmount you have to set `isB2B` on the ' .
                    PaymentConfigSearch::class . '. You can set the flag to `false` if no B2B is required'
                );
            }
            $qb->andWhere($qb->expr()->lte('payment_config.limitMin', ':total_amount'));

            $maxExpression = $qb->expr()->gte('payment_config.limitMax', ':total_amount');
            if ($configSearch->isB2b() === true) {
                $maxExpression = $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->lte('payment_config.limitMaxB2b', 0),
                        $qb->expr()->gte('payment_config.limitMax', ':total_amount')
                    ),
                    $qb->expr()->gte('payment_config.limitMaxB2b', ':total_amount')
                );
            }
            $qb->andWhere($maxExpression);
            $qb->setParameter('total_amount', $configSearch->getTotalAmount());
        }

        $qb->setParameter('billing_country_code', '%' . $configSearch->getBillingCountry() . '%');
        $qb->setParameter('shipping_country_code', '%' . $configSearch->getShippingCountry() . '%');
        $qb->setParameter('currency', '%' . $configSearch->getCurrency() . '%');
        $qb->setParameter('shop_id', $configSearch->getShop());
        $qb->setParameter('backend', $configSearch->isBackend());
        $qb->setParameter('payment_method_id', $configSearch->getPaymentMethod());

        return $qb;
    }
}
