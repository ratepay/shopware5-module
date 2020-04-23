<?php

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * RpayRatepayBackendOrder
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */

use Monolog\Logger;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\InstallmentService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Address;

class Shopware_Controllers_Backend_RatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ConfigService
     */
    protected $config;
    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;
    /**
     * @var InstallmentService
     */
    protected $installmentService;
    /**
     * @var SessionHelper
     */
    protected $sessionHelper;

    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);
        $this->logger = Shopware()->Container()->get('rpay_rate_pay.logger');
        $this->config = $this->container->get(ConfigService::class);
        $this->modelManager = $this->container->get('models');
        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->installmentService = $this->container->get(InstallmentService::class);
        $this->sessionHelper = $this->container->get(SessionHelper::class);
    }

    /**
     * Write to session. We must because there is no way to send extra data with order create request.
     */
    public function setBankDataAction()
    {
        $params = $this->Request()->getParams();

        $accountNumber = trim($params['iban']);
        $customerId = intval($params['customerId']);

        if (ValidationLib::isIbanValid($params['iban'])) {
            $this->sessionHelper->setBankData($customerId, $accountNumber, null);
            $this->view->assign([
                'success' => true,
            ]);
        } else {
            $this->view->assign([
                'success' => false,
                'messages' => [$this->getSnippet('backend/ratepay', 'InvalidIban', null)]
            ]);
        }


    }

    public function getInstallmentInfoAction()
    {
        //TODO: add try/catch block
        try {
            $params = $this->Request()->getParams();

            $shopId = $params['shopId'];
            $billingId = $params['billingId'];
            //TODO: change array key to paymentMeansName
            $paymentMeansName = $params['paymentTypeName'];
            $totalAmount = $params['totalAmount'];

            $customerAddress = $this->modelManager->find(Address::class, $billingId);

            $result = $this->installmentService->getInstallmentCalculator(
                $customerAddress,
                $shopId,
                $paymentMeansName,
                true,
                $totalAmount
            );

            $this->view->assign([
                'success' => true,
                'termInfo' => $result
            ]);
        } catch (Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => ['An error occurred while loading the calculator (Exception: ' . $e->getMessage() . ')']
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

        try {
            $customerAddress = $this->modelManager->find(Address::class, $billingId);

            $installmentConfig = $this->profileConfigService->getInstallmentConfig($paymentMeansName, $shopId, $customerAddress->getCountry()->getIso(), true);
            if ($installmentConfig == null) {
                throw new NoProfileFoundException();
            }

            $optionsString = $installmentConfig->getPaymentFirstDay();
            $optionsArray = explode(',', $optionsString);
            $optionsIntArray = array_map('intval', $optionsArray);
            $this->view->assign([
                'success' => true,
                'options' => $optionsIntArray
            ]);
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
            return;
        }
    }

    public function getInstallmentPlanAction()
    {
        $params = $this->Request()->getParams();

        $shopId = $params['shopId'];
        $billingId = $params['billingId'];

        $billingAddress = $this->modelManager->find(Address::class, $billingId);

        $paymentMethodName = $params['paymentMeansName'];
        $totalAmount = $params['totalAmount'];
        $paymentFirstDay = $params['paymentSubtype']; //todo this is the paymentFirstDay
        $calcParamSet = !empty($params['value']) && !empty($params['type']);
        $type = $calcParamSet ? $params['type'] : 'time';

        $installmentConfig = $this->profileConfigService->getInstallmentConfig($paymentMethodName, $shopId, $billingAddress->getCountry()->getIso(), true);

        //TODO refactor
        if ($calcParamSet) {
            $val = $params['value'];
        } else {
            $val = explode(',', $installmentConfig->getMonthAllowed())[0];
        }

        try {
            $dto = new InstallmentRequest(
                $totalAmount,
                $type,
                $val,
                null,
                $paymentFirstDay
            );

            $plan = $this->installmentService->initInstallmentData(
                $billingAddress,
                $shopId,
                $paymentMethodName,
                true,
                $dto
            );
            $this->view->assign([
                'success' => true,
                'plan' => $plan,
            ]);
        } catch (Exception $e) {
            $this->view->assign([
                'success' => false,
                'messages' => [$e->getMessage()]
            ]);
        }

    }

    public function updatePaymentSubtypeAction()
    {
        $params = $this->Request()->getParams();
        $this->sessionHelper->setInstallmentPaymentSubtype($params['paymentSubtype']);
    }

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
}
