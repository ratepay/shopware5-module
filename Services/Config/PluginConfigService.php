<?php

namespace RpayRatePay\Services;
//TODO remove this class
class XXXConfigService
{
    private $db;

    private $config;

    public function __construct(\Shopware_Components_Config $config, \Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
        $this->config = $config;
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
}
