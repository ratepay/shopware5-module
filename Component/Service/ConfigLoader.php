<?php

namespace RpayRatePay\Component\Service;

use RpayRatePay\Services\ConfigService;
use RpayRatePay\Services\ProfileConfigService;

class ConfigLoader
{
    private $db;

    private $config;

    /**
     * ConfigLoader constructor.
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;

        //TODO parameterize
        $this->config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
    }

    private function getPaymentMeansMap()
    {
        return [
            'rpayratepayrate' => 'installment',
            'rpayratepayinvoice' => 'invoice',
            'rpayratepaydebit' => 'debit',
            'rpayratepayrate0' => 'installment0',
            'rpayratepayprepayment' => 'prepayment'
        ];
    }

    /**
     * @param $paymentName
     * @return string|null
     */
    public function getPaymentColumnFromPaymentMeansName($paymentName)
    {
        $map = $this->getPaymentMeansMap();
        if (array_key_exists($paymentName, $map)) {
            return $map[$paymentName];
        } else {
            return null;
        }
    }

    /**
     * @param int $shopId
     * @param string $countryISO
     * @param string $paymentColumn
     * @param bool $backend
     * @return mixed
     */
    public function getPluginConfigForPaymentType($shopId, $countryISO, $paymentColumn, $backend = false)
    {
        $profileConfig = ProfileConfigService::getProfileConfig(
            $countryISO,
            $shopId,
            $paymentColumn == 'installment0',
            $backend
        );

        if($profileConfig === null) {
            return null;
        }

        $qry = 'SELECT *
                        FROM `rpay_ratepay_config` AS rrc
                          JOIN `rpay_ratepay_config_payment` AS rrcp
                            ON rrcp.`rpay_id` = rrc.`' . $paymentColumn . '`
                          LEFT JOIN `rpay_ratepay_config_installment` AS rrci
                            ON rrci.`rpay_id` = rrc.`' . $paymentColumn . "`
                        WHERE rrc.`shopId` = '" . $shopId . "'
                             AND rrc.`profileId`= '" . $profileConfig->getProfileId() . "'
                             AND rrc.`country_code_billing`= '" . $profileConfig->getCountryCodeBilling() . "'
                             AND rrc.`is_zero_percent_installment`= '" . ($paymentColumn == 'installment0' ? 1 : 0) . "'
                             AND rrc.`backend` = ".($profileConfig->isBackend() ? 1 : 0);

        $result = Shopware()->Db()->fetchRow($qry);

        return $result;
    }

    /**
     * @param null $shopId
     * @return bool
     * @deprecated use ConfigService
     */
    public function commitDiscountAsCartItem($shopId = null) {
        return ConfigService::getInstance()->getConfig('RatePayUseFallbackDiscountItem', false, $shopId) == 1;
    }

    /**
     * @param null $shopId
     * @return bool
     * @deprecated use ConfigService
     */
    public function commitShippingAsCartItem($shopId = null) {
        return ConfigService::getInstance()->getConfig('RatePayUseFallbackShippingItem', false, $shopId) == 1;
    }
}
