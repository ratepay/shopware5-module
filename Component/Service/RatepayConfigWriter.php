<?php

namespace RpayRatePay\Component\Service;

use RpayRatePay\Models\ProfileConfig;
use Shopware\Components\Model\ModelManager;
use RpayRatePay\Services\ProfileConfigService;

class RatepayConfigWriter
{
    private $db;

    /** @var ModelManager  */
    protected $modelManager;

    public function __construct($db)
    {
        $this->db = $db;
        $this->modelManager = Shopware()->Models(); //TODO remove it plugin is moved to the SW5.2 plugin engine
    }

    /**
     * @return bool
     */
    public function truncateConfigTables()
    {
        $schemaManager = $this->modelManager->getConnection()->getSchemaManager();

        $tables = [
            'rpay_ratepay_config',
            'rpay_ratepay_config_payment',
            'rpay_ratepay_config_installment'
        ];
        try {
            $this->db->query("SET FOREIGN_KEY_CHECKS=0");
            foreach($tables as $table) {
                if($schemaManager->tablesExist([$table])) {
                    $this->db->query('TRUNCATE TABLE `'.$table.'`;');
                }
            }
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
        } catch (\Exception $exception) {
            Logger::singleton()->info($exception->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Sends a Profile_request and saves the data into the Database
     *
     * @param string $profileId
     * @param string $securityCode
     * @param int $shopId
     * @param string $country
     * @param bool $backend
     *
     * @return bool
     */
    public function writeRatepayConfig($profileId, $securityCode, $shopId, $backend = false)
    {
        $profileId = trim($profileId);
        $securityCode = trim($securityCode);
        $factory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend, null, $shopId);
        $data = [
            'profileId' => $profileId,
            'securityCode' => $securityCode
        ];

        try {
            $response = $factory->callProfileRequest($data);
        } catch (\Exception $e) {
            Logger::singleton()->error(
                'RatePAY: Profile_Request failed for profileId ' . $profileId
            );
            return false;
        }

        if (!is_array($response) || $response === false) {
            Logger::singleton()
                ->info('RatePAY: Profile_Request for profileId ' . $profileId . ' was empty ');
            return false;
        }

        $countries = explode(',',$response['result']['merchantConfig']['country-code-billing']);
        foreach($countries as $country) {

            $payments = ['invoice', 'elv', 'installment', 'installment0', 'prepayment'];

            $type = [];
            //INSERT INTO rpay_ratepay_config_payment AND sets $type[]
            foreach ($payments as $payment) {
                if (strpos($profileId, '_0RT') === false && $payment === 'installment0' ||
                    strpos($profileId, '_0RT') !== false && $payment !== 'installment0'
                ) {
                    continue;
                }

                $pseudoPayment = $payment;
                if ($payment === 'installment0') {
                    $pseudoPayment = 'installment';
                }

                $dataPayment = [
                    $response['result']['merchantConfig']['activation-status-' . $pseudoPayment],
                    $response['result']['merchantConfig']['b2b-' . $pseudoPayment] == 'yes' ? 1 : 0,
                    $response['result']['merchantConfig']['tx-limit-' . $pseudoPayment . '-min'],
                    $response['result']['merchantConfig']['tx-limit-' . $pseudoPayment . '-max'],
                    $response['result']['merchantConfig']['tx-limit-' . $pseudoPayment . '-max-b2b'],
                    $response['result']['merchantConfig']['delivery-address-' . $pseudoPayment] == 'yes' ? 1 : 0,
                ];

                $paymentSql = 'INSERT INTO `rpay_ratepay_config_payment`'
                    . '(`status`, `b2b`,`limit_min`,`limit_max`,'
                    . '`limit_max_b2b`, `address`)'
                    . 'VALUES(' . substr(str_repeat('?,', 6), 0, -1) . ');';
                try {
                    $this->db->query($paymentSql, $dataPayment);
                    $id = $this->db->fetchOne('SELECT `rpay_id` FROM `rpay_ratepay_config_payment` ORDER BY `rpay_id` DESC');
                    $type[$payment] = $id;
                } catch (\Exception $exception) {
                    Logger::singleton()->error($exception->getMessage());
                    return false;
                }
            }

            //performs insert into the 'config installment' table
            if ($response['result']['merchantConfig']['activation-status-installment'] == 2) {
                $installmentConfig = [
                    isset($type['installment0']) ? $type['installment0'] : $type['installment'],
                    $response['result']['installmentConfig']['month-allowed'],
                    $response['result']['installmentConfig']['valid-payment-firstdays'],
                    $response['result']['installmentConfig']['rate-min-normal'],
                    $response['result']['installmentConfig']['interestrate-default'],
                ];
                $paymentSql = 'INSERT INTO `rpay_ratepay_config_installment`'
                    . '(`rpay_id`, `month_allowed`,`payment_firstday`,`interestrate_default`,'
                    . '`rate_min_normal`)'
                    . 'VALUES(' . substr(str_repeat('?,', 5), 0, -1) . ');';
                try {
                    $this->db->query($paymentSql, $installmentConfig);
                } catch (\Exception $exception) {
                    Logger::singleton()->error($exception->getMessage());
                    return false;
                }
            }

            $configModel = new ProfileConfig();
            $configModel->setProfileId($response['result']['merchantConfig']['profile-id']);
            $configModel->setSecurityCode($securityCode);
            $configModel->setInvoice($type['invoice']);
            $configModel->setInstallment($type['installment']);
            $configModel->setDebit($type['elv']);
            $configModel->setInstallment0($type['installment0']);
            $configModel->setIsZeroPercentInstallment($configModel->getInstallment0() !== null);
            $configModel->setInstallmentDebit(null); // TODO why there is no value?
            $configModel->setPrepayment($type['prepayment']);
            $configModel->setCountryCodeBilling($country);
            $configModel->setCountryCodeDelivery(strtoupper($response['result']['merchantConfig']['country-code-delivery']));
            $configModel->setCurrency(strtoupper($response['result']['merchantConfig']['currency']));
            //$configModel->setCountry(strtoupper($country));
            $configModel->setSandbox($response['sandbox'] == 1);
            $configModel->setBackend($backend == 1);
            $configModel->setShopId($shopId);

            $qb = Shopware()->Models()->createQueryBuilder();
            $qb->select('c')
                ->from(ProfileConfig::class, 'c')
                ->andWhere($qb->expr()->eq('c.shopId', ':shop_id'))
                ->andWhere($qb->expr()->eq('c.backend', ':is_backend'))
                ->andWhere($qb->expr()->eq('c.countryCodeBilling', ':country_code'))
                ->andWhere($qb->expr()->eq('c.isZeroPercentInstallment', ':is_zero_percent_installment'))
                ->setParameter('shop_id', $configModel->getShopId())
                ->setParameter('is_backend', $configModel->isBackend())
                ->setParameter('country_code', $configModel->getCountryCodeBilling())
                ->setParameter('is_zero_percent_installment', $configModel->isZeroPercentInstallment())
                ->setMaxResults(1);

            if($qb->getQuery()->getOneOrNullResult() !== null) {
                //this profile has been already saved
                return true;
            }

            $activePayments[] = '"rpayratepayinvoice"';
            $activePayments[] = '"rpayratepaydebit"';
            $activePayments[] = '"rpayratepayrate"';
            $activePayments[] = '"rpayratepayrate0"';
            $activePayments[] = '"rpayratepayprepayment"';

            $updateSqlActivePaymentMethods = 'UPDATE `s_core_paymentmeans` SET `active` = 1 WHERE `name` in(' . implode(',', $activePayments) . ') AND `active` <> 0';

            try {
                $this->modelManager->persist($configModel);
                $this->modelManager->flush($configModel);

                if (count($activePayments) > 0) {
                    $this->db->query($updateSqlActivePaymentMethods);
                }

            } catch (\Exception $exception) {
                Logger::singleton()->error($exception->getMessage());
                return false;
            }
        }
        return true;
    }
}
