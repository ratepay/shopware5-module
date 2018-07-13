<?php
/**
 * Created by PhpStorm.
 * User: awhittington
 * Date: 11.07.18
 * Time: 09:14
 */
namespace RpayRatePay\Component\Service;

class ConfigLoader
{
    private $db;
    /**
     * ConfigLoader constructor.
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }


    private function getPaymentMeansMap() {
        return [
            "rpayratepayrate" => "installment",
            "rpayratepayinvoice" => "invoice",
            "rpayratepaydebit" => "debit",
            "rpayratepayrate0" => "installment0"
        ];
    }

    /**
     * @param $paymentName
     * @return string|null
     */
    public function getPaymentColumnFromPaymentMeansName($paymentName)
    {
        $map = $this->getPaymentMeansMap();
        if( array_key_exists($paymentName, $map)) {
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
        $configKey = self::getProfileIdKey($countryISO, $backend);
        $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get($configKey, $shopId);

        $sBackend = $backend ? '1' : '0';

        $qry = "SELECT * 
                        FROM `rpay_ratepay_config` AS rrc
                          JOIN `rpay_ratepay_config_payment` AS rrcp
                            ON rrcp.`rpay_id` = rrc.`" . $paymentColumn . "`
                          LEFT JOIN `rpay_ratepay_config_installment` AS rrci
                            ON rrci.`rpay_id` = rrc.`" . $paymentColumn . "`
                        WHERE rrc.`shopId` = '" . $shopId . "'
                             AND rrc.`profileId`= '" . $profileId . "'
                        AND rrc.backend=$sBackend";
        $result = Shopware()->Db()->fetchRow($qry);

        return $result;
    }

    /**
     * @param string $countryISO
     * @param bool $backend
     * @return string
     */
    public static function getProfileIdKey($countryISO, $backend = false)
    {
        $profileId = 'RatePayProfileID' . $countryISO;
        if ($backend) {
            $profileId .= 'Backend';
        }
        return $profileId;
    }

    public static function getSecurityCodeKey($countryISO, $backend = false)
    {
        $securityCodeKey = 'RatePaySecurityCode' . $countryISO;
        if ($backend) {
            $securityCodeKey .= 'Backend';
        }
        return $securityCodeKey;
    }
}