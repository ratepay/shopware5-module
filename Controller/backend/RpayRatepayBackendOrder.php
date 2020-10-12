<?php

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\Logger;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Models\ConfigRepository;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\ConfigService;

class Shopware_Controllers_Backend_RpayRatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @param string $namespace
     * @param string $name
     * @param string $default
     * @return mixed
     */
    private function getSnippet($namespace, $name, $default)
    {
        $ns = Shopware()->Snippets()->getNamespace($namespace);
        return $ns->get($name, $default);
    }

    public function setContainer(\Shopware\Components\DependencyInjection\Container $loader = null)
    {
        parent::setContainer($loader);
        $this->configService = ConfigService::getInstance();
    }

    /**
     * Write to session. We must because there is no way to send extra data with order create request.
     */
    public function setExtendedDataAction()
    {
        Logger::singleton()->info('Now calling setExtendedData');
        $params = $this->Request()->getParams();

        $iban = trim($params['iban']);
        $customerId = $params['customerId'];

        $sessionLoader = new SessionLoader(Shopware()->BackendSession());

        $sessionLoader->setBankData($customerId, $iban);

        $this->view->assign([
            'success' => true,
        ]);
    }

    /**
     * @param ProfileConfig $profileConfig
     * @return \RatePAY\Frontend\InstallmentBuilder
     */
    private function getInstallmentBuilder(ProfileConfig $profileConfig)
    {
        $installmentBuilder = new RatePAY\Frontend\InstallmentBuilder($profileConfig->isSandbox());
        $installmentBuilder->setProfileId($profileConfig->getProfileId());
        $installmentBuilder->setSecurityCode($profileConfig->getSecurityCode());
        return $installmentBuilder;
    }

    /**
     * @param $paymentMeansName
     * @param $addressObj
     * @param $shopId
     * @return \RatePAY\Frontend\InstallmentBuilder
     */
    private function getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId)
    {
        $countryIso = PaymentRequestData::findCountryISO($addressObj);

        //TODO: put magic strings in consts
        $nullPercent = $paymentMeansName === 'rpayratepayrate0';

        /** @var ConfigRepository $repo */
        $repo = Shopware()->Models()->getRepository(ProfileConfig::class);
        $profileConfig = $repo->findConfiguration($shopId, $countryIso, $nullPercent, true);
        if($profileConfig === null) {
            throw new Shopware_Plugins_Frontend_RpayRatePay_Exception_ProfileNotFoundException();
        }

        $installmentBuilder = $this->getInstallmentBuilder($profileConfig);
        return $installmentBuilder;
    }

    /**
     * @param $paymentMeansName
     * @param $addressObj
     * @param $shopId
     * @return array
     */
    private function getConfig($paymentMeansName, $addressObj, $shopId)
    {
        $configLoader = new ConfigLoader(Shopware()->Db());
        $paymentColumn = $configLoader->getPaymentColumnFromPaymentMeansName($paymentMeansName);

        $countryIso = PaymentRequestData::findCountryISO($addressObj);

        $config = $configLoader->getPluginConfigForPaymentType(
            $shopId,
            $countryIso,
            $paymentColumn,
            true
        );
        return $config;
    }

    /**
     * @param $paymentMeansName
     * @param object $addressObj
     * @param int $shopId
     * @return mixed
     */
    private function getTermFallback($paymentMeansName, $addressObj, $shopId)
    {
        $config = $this->getConfig($paymentMeansName, $addressObj, $shopId);
        $termString = $config['month_allowed'];
        $termArray = explode(',', $termString);
        return $termArray[0];
    }

    public function getInstallmentInfoAction()
    {
        //TODO: add try/catch block
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];
        //TODO: change array key to paymentMeansName
        $paymentMeansName = $params['paymentTypeName'];
        $totalAmount = $params['totalAmount'];

        $addressObj = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        try {
            $installmentBuilder = $this->getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId);
            $result = $installmentBuilder->getInstallmentCalculatorAsJson($totalAmount);

            $this->view->assign([
                'success' => true,
                'termInfo' => json_decode($result, true)
            ]);
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * Returns array of ints containing 2 or 28.
     */
    public function getInstallmentPaymentOptionsAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];
        $paymentMeansName = $params['paymentMeansName'];

        $addressObj = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        $config = $this->getConfig($paymentMeansName, $addressObj, $shopId);
        $optionsString = $config['payment_firstday'];
        $optionsArray = explode(',', $optionsString);
        $optionsIntArray = array_map('intval', $optionsArray);

        $this->view->assign([
            'success' => true,
            'options' => $optionsIntArray
        ]);
    }

    public function getInstallmentPlanAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];

        $addressObj = Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingId);

        $paymentMeansName = $params['paymentMeansName'];

        $totalAmount = $params['totalAmount'];

        $paymentSubtype = $params['paymentSubtype'];

        $calcParamSet = !empty($params['value']) && !empty($params['type']);

        $type = $calcParamSet ? $params['type'] : 'time';
        $val = $calcParamSet ? $params['value'] : $this->getTermFallback($paymentMeansName, $addressObj, $shopId);


        try {
            $installmentBuilder = $this->getInstallmentBuilderFromConfig($paymentMeansName, $addressObj, $shopId);
            $result = $installmentBuilder->getInstallmentPlanAsJson($totalAmount, $type, $val);
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
            return;
        }

        $plan = json_decode($result, true);

        $sessionLoader = new SessionLoader(Shopware()->BackendSession());

        $sessionLoader->setInstallmentData(
            $plan['totalAmount'],
            $plan['amount'],
            $plan['interestRate'],
            $plan['interestAmount'],
            $plan['serviceCharge'],
            $plan['annualPercentageRate'],
            $plan['monthlyDebitInterest'],
            $plan['numberOfRatesFull'],
            $plan['rate'],
            $plan['lastRate'],
            $paymentSubtype //$plan['paymentFirstday']
        );

        $this->view->assign([
            'success' => true,
            'plan' => $plan,
        ]);
    }

    public function updatePaymentSubtypeAction()
    {
        $params = $this->Request()->getParams();
        $sessionLoader = new SessionLoader(Shopware()->BackendSession());
        $sessionLoader->setInstallmentPaymentSubtype($params['paymentSubtype']);
    }
}
