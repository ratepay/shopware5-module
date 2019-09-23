<?php


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
