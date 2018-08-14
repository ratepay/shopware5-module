<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:58
 */
namespace RpayRatePay\Bootstrapping\Events;

class PluginConfigurationSubscriber implements \Enlight\Event\SubscriberInterface
{
    protected $_countries = array('de', 'at', 'ch', 'nl', 'be');

    /**
     * @var string
     */
    private $name;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PluginConfigurationSubscriber constructor.
     * @param $name string name of plugin
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Config::saveFormAction::before' => 'beforeSavePluginConfig',
        ];
    }

    /**
     * Checks if credentials are set and gets the configuration via profile_request
     *
     * @param \Enlight_Hook_HookArgs $arguments
     *
     * @return null
     */
    public function beforeSavePluginConfig(\Enlight_Hook_HookArgs $arguments)
    {
        $request = $arguments->getSubject()->Request();
        $parameter = $request->getParams();

        if ($parameter['name'] !== $this->name || $parameter['controller'] !== 'config') {
            return;
        }

        $shopCredentials = array();

        // Remove old configs
        $this->_truncateConfigTables();

        foreach ($parameter['elements'] as $element) {
            foreach ($this->_countries AS $country) {
                if ($element['name'] === 'RatePayProfileID' . strtoupper($country)) {
                    foreach($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileID'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePaySecurityCode'  . strtoupper($country)) {
                    foreach($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCode'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePayProfileID' . strtoupper($country) . 'Backend') {
                    foreach($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['profileIDBackend'] = trim($element['value']);
                    }
                }
                if ($element['name'] === 'RatePaySecurityCode' . strtoupper($country) . 'Backend') {
                    foreach($element['values'] as $element) {
                        $shopCredentials[$element['shopId']][$country]['securityCodeBackend'] = trim($element['value']);
                    }
                }
            }
        }

        foreach($shopCredentials as $shopId => $credentials) {
            foreach ($this->_countries AS $country) {
                if (null !== $credentials[$country]['profileID'] &&
                    null !== $credentials[$country]['securityCode']) {
                    if ($this->getRatepayConfig($credentials[$country]['profileID'], $credentials[$country]['securityCode'], $shopId, $country)) {
                        Shopware()->PluginLogger()->addNotice('Ruleset for ' . strtoupper($country) . ' successfully updated.');
                    }
                    if ($country == 'de') {
                        if ($this->getRatepayConfig($credentials[$country]['profileID'] . '_0RT', $credentials[$country]['securityCode'], $shopId, $country)) {
                            Shopware()->PluginLogger()->addNotice('Ruleset 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
                if (null !== $credentials[$country]['profileIDBackend'] &&
                    null !== $credentials[$country]['securityCodeBackend']) {
                    if ($this->getRatepayConfig($credentials[$country]['profileIDBackend'], $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                        Shopware()->PluginLogger()->addNotice('Ruleset BACKEND for ' . strtoupper($country) . ' successfully updated.');
                    }
                    if ($country == 'de') {
                        if ($this->getRatepayConfig($credentials[$country]['profileIDBackend'] . '_0RT', $credentials[$country]['securityCodeBackend'], $shopId, $country, true)) {
                            Shopware()->PluginLogger()->addNotice('Ruleset BACKEND 0RT for ' . strtoupper($country) . ' successfully updated.');
                        }
                    }
                }
            }
        }
    }

    /**
     * Truncate config tables
     *
     * @return bool
     */
    private function _truncateConfigTables()
    {
        $configSql = 'TRUNCATE TABLE `rpay_ratepay_config`;';
        $configPaymentSql = 'TRUNCATE TABLE `rpay_ratepay_config_payment`;';
        $configInstallmentSql = 'TRUNCATE TABLE `rpay_ratepay_config_installment`;';
        try {
            Shopware()->Db()->query($configSql);
            Shopware()->Db()->query($configPaymentSql);
            Shopware()->Db()->query($configInstallmentSql);
        } catch (\Exception $exception) {
            Shopware()->Pluginlogger()->info($exception->getMessage());
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
     * @return mixed
     * @throws exception
     */
    private function getRatepayConfig($profileId, $securityCode, $shopId, $country, $backend = false)
    {
        $factory = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory(null, $backend);
        $data = array(
            'profileId' => $profileId,
            'securityCode' => $securityCode
        );

        $response = $factory->callRequest('ProfileRequest', $data);

        $payments = array('invoice', 'elv', 'installment');

        if (is_array($response) && $response !== false) {

            foreach ($payments AS $payment) {
                if (strstr($profileId, '_0RT') !== false) {
                    if ($payment !== 'installment') {
                        continue;
                    }
                }

                $dataPayment = array(
                    $response['result']['merchantConfig']['activation-status-' . $payment],
                    $response['result']['merchantConfig']['b2b-' . $payment] == 'yes' ? 1 : 0,
                    $response['result']['merchantConfig']['tx-limit-' . $payment . '-min'],
                    $response['result']['merchantConfig']['tx-limit-' . $payment . '-max'],
                    $response['result']['merchantConfig']['tx-limit-' . $payment . '-max-b2b'],
                    $response['result']['merchantConfig']['delivery-address-'  . $payment] == 'yes' ? 1 : 0,
                );

                $paymentSql = 'INSERT INTO `rpay_ratepay_config_payment`'
                    . '(`status`, `b2b`,`limit_min`,`limit_max`,'
                    . '`limit_max_b2b`, `address`)'
                    . 'VALUES(' . substr(str_repeat('?,', 6), 0, -1) . ');';
                try {
                    Shopware()->Db()->query($paymentSql, $dataPayment);
                    $id = Shopware()->Db()->fetchOne('SELECT `rpay_id` FROM `rpay_ratepay_config_payment` ORDER BY `rpay_id` DESC');
                    $type[$payment] = $id;
                } catch (\Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                    return false;
                }
            }

            if ($response['result']['merchantConfig']['activation-status-installment']  == 2) {
                $installmentConfig = array(
                    $type['installment'],
                    $response['result']['installmentConfig']['month-allowed'],
                    $response['result']['installmentConfig']['valid-payment-firstdays'],
                    $response['result']['installmentConfig']['rate-min-normal'],
                    $response['result']['installmentConfig']['interestrate-default'],
                );
                $paymentSql = 'INSERT INTO `rpay_ratepay_config_installment`'
                    . '(`rpay_id`, `month-allowed`,`payment-firstday`,`interestrate-default`,'
                    . '`rate-min-normal`)'
                    . 'VALUES(' . substr(str_repeat('?,', 5), 0, -1) . ');';
                try {
                    Shopware()->Db()->query($paymentSql, $installmentConfig);
                } catch (\Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());
                    return false;
                }

            }

            if (strstr($profileId, '_0RT') !== false) {
                $qry = "UPDATE rpay_ratepay_config SET installment0 = '" . $type['installment'] . "' WHERE profileId = '" . substr($profileId, 0, -4) . "'";
                Shopware()->Db()->query($qry);
            } else {
                $data = array(
                    $response['result']['merchantConfig']['profile-id'],
                    $type['invoice'],
                    $type['installment'],
                    $type['elv'],
                    0,
                    0,
                    $response['result']['merchantConfig']['eligibility-device-fingerprint'] ? : 'no',
                    $response['result']['merchantConfig']['device-fingerprint-snippet-id'],
                    strtoupper($response['result']['merchantConfig']['country-code-billing']),
                    strtoupper($response['result']['merchantConfig']['country-code-delivery']),
                    strtoupper($response['result']['merchantConfig']['currency']),
                    strtoupper($country),
                    $response['sandbox'],
                    $backend,
                    //shopId always needs be the last line
                    $shopId
                );

                $activePayments[] = '"rpayratepayinvoice"';
                $activePayments[] = '"rpayratepaydebit"';
                $activePayments[] = '"rpayratepayrate"';
                $activePayments[] = '"rpayratepayrate0"';

                if (count($activePayments) > 0) {
                    $updateSqlActivePaymentMethods = 'UPDATE `s_core_paymentmeans` SET `active` = 1 WHERE `name` in(' . implode(",", $activePayments) . ') AND `active` <> 0';
                }
                $configSql = 'INSERT INTO `rpay_ratepay_config`'
                    . '(`profileId`, `invoice`, `installment`, `debit`, `installment0`, `installmentDebit`,'
                    . '`device-fingerprint-status`, `device-fingerprint-snippet-id`,'
                    . '`country-code-billing`, `country-code-delivery`,'
                    . '`currency`,`country`, `sandbox`,'
                    . '`backend`, `shopId`)'
                    . 'VALUES(' . substr(str_repeat('?,', 15), 0, -1) . ');'; // In case of altering cols change 14 by amount of affected cols
                try {
                    Shopware()->Db()->query($configSql, $data);
                    if (count($activePayments) > 0) {
                        Shopware()->Db()->query($updateSqlActivePaymentMethods);
                    }

                    return true;
                } catch (\Exception $exception) {
                    Shopware()->Pluginlogger()->error($exception->getMessage());

                    return false;
                }
            }
        } else {
            Shopware()->Pluginlogger()->error('RatePAY: Profile_Request failed!');

            if (strstr($profileId, '_0RT') == false) {
                throw new Exception('RatePAY: Profile_Request failed!');
            }
        }
    }
}