<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_ShopConfigSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    protected $availableCountries = array(
        'de',
        'at',
        'ch',
        'nl',
        'be'
    );

    /**
     * @throws Exception
     */
    public function install() {}

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $credentials = $this->loadShopCredentials();

        if (!empty($credentials)) {
            foreach ($credentials as $country => $shop) {
                foreach ($shop['id'] as $item) {
                    $this->getRatepayConfig($shop['profile'], $shop['security'], $item['shop_id'], $country);
                    if ($country == 'de') {
                        $this->getRatepayConfig($shop['profile'] . '_0RT', $shop['security'], $item['shop_id'], $country);
                    }
                }
            }
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}

    /**
     * @return mixed
     */
    public function loadShopCredentials()
    {
        $shopConfig = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();

        return array_reduce($this->availableCountries, function ($shops, $country) use ($shopConfig) {
            $profile = $shopConfig->get('RatePayProfileID' . strtoupper($country));
            $security = $shopConfig->get('RatePaySecurityCode' . strtoupper($country));

            if (!empty($profile)) {
                $id = Shopware()->Db()
                    ->query("SELECT `shop_id` FROM `s_core_config_values` WHERE `value` LIKE '%" . $profile . "%'");
                $shops[$country] = compact('profile', 'security', 'id');
            }

            return $shops;
        }, []);
    }

    /**
     * Sends a Profile_request and saves the data into the Database
     *
     * @param string $profileId
     * @param string $securityCode
     * @param int $shopId
     * @param string $country
     *
     * @return mixed
     * @throws exception
     */
    private function getRatepayConfig($profileId, $securityCode, $shopId, $country)
    {
        $factory = new Shopware_Plugins_Frontend_RpayRatePay_Component_Mapper_ModelFactory();
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

                $paymentSql = 'REPLACE INTO `rpay_ratepay_config_payment`'
                    . '(`status`, `b2b`,`limit_min`,`limit_max`,'
                    . '`limit_max_b2b`, `address`)'
                    . 'VALUES(' . substr(str_repeat('?,', 6), 0, -1) . ');';
                try {
                    Shopware()->Db()->query($paymentSql, $dataPayment);
                    $id = Shopware()->Db()->fetchOne('SELECT `rpay_id` FROM `rpay_ratepay_config_payment` ORDER BY `rpay_id` DESC');
                    $type[$payment] = $id;
                } catch (Exception $exception) {
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
                $paymentSql = 'REPLACE INTO `rpay_ratepay_config_installment`'
                    . '(`rpay_id`, `month-allowed`,`payment-firstday`,`interestrate-default`,'
                    . '`rate-min-normal`)'
                    . 'VALUES(' . substr(str_repeat('?,', 5), 0, -1) . ');';
                try {
                    Shopware()->Db()->query($paymentSql, $installmentConfig);
                } catch (Exception $exception) {
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


                $configSql = 'REPLACE INTO `rpay_ratepay_config`'
                    . '(`profileId`, `invoice`, `installment`, `debit`, `installment0`, `installmentDebit`,'
                    . '`device-fingerprint-status`, `device-fingerprint-snippet-id`,'
                    . '`country-code-billing`, `country-code-delivery`,'
                    . '`currency`,`country`, `sandbox`,'
                    . ' `shopId`)'
                    . 'VALUES(' . substr(str_repeat('?,', 14), 0, -1) . ');'; // In case of altering cols change 14 by amount of affected cols
                try {
                    Shopware()->Db()->query($configSql, $data);
                    if (count($activePayments) > 0) {
                        Shopware()->Db()->query($updateSqlActivePaymentMethods);
                    }

                    return true;
                } catch (Exception $exception) {
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