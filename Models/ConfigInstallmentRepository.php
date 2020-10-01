<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;


use Shopware\Components\Model\ModelRepository;

class ConfigInstallmentRepository extends ModelRepository
{

    /**
     * @param ConfigPayment $paymentConfig
     * @return ConfigInstallment|null
     */
    public function findOneByPaymentConfig(ConfigPayment $paymentConfig)
    {
        return $this->findOneBy(['paymentConfig' => $paymentConfig->getRpayId()]);
    }
}
