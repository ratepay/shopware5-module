<?php


namespace RpayRatePay\Models;


use RpayRatePay\Models\ConfigPayment;
use Shopware\Components\Model\ModelRepository;

class ConfigInstallmentRepository extends ModelRepository
{

    /**
     * @param \RpayRatePay\Models\ConfigPayment $paymentConfig
     * @return ConfigInstallment|null
     */
    public function findOneByPaymentConfig(ConfigPayment $paymentConfig) {
        return $this->findOneBy(['paymentConfig' => $paymentConfig->getRpayId()]);
    }
}
