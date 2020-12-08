<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Config;


use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use Monolog\Logger;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Models\ProfileConfigRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment as PaymentMethod;
use Shopware\Models\Shop\Shop;

class ProfileConfigService
{
    const REGEX_CONFIG = '/ratepay\/profile\/([a-z]{2})\/(frontend|backend)\/(id|security_code)\/?(installment0)?/';
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var WriterService
     */
    private $configWriterService;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ModelManager $modelManager,
        ConfigService $configService,
        WriterService $configWriterService,
        Logger $logger
    )
    {
        $this->configService = $configService;
        $this->modelManager = $modelManager;
        $this->configWriterService = $configWriterService;
        $this->logger = $logger;
    }

    /**
     * @param PaymentMethod $paymentMethodName string
     * @param $shopId
     * @param $countryIso
     * @param bool $backend
     * @return object|ConfigInstallment|null
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getInstallmentConfig($paymentMethodName, $shopId, $countryIso = null, $backend = false)
    {
        // TODO naming is similar to `getPaymentConfig`, but do other stuffs
        $config = $this->getPaymentConfig($paymentMethodName, $shopId, $countryIso, $backend);
        return $config ? $this->modelManager->find(ConfigInstallment::class, $config->getRpayId()) : null;
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @param $shopId int
     * @param $countryIso
     * @param $backend
     * @return ConfigPayment|null
     */
    protected function getPaymentConfig($paymentMethod, $shopId, $countryIso = null, $backend = null)
    {
        $paymentMethod = $paymentMethod instanceof PaymentMethod ? $paymentMethod->getName() : $paymentMethod;
        $profileConfig = $this->getProfileConfig(
            $countryIso,
            $shopId,
            $backend,
            $paymentMethod == PaymentMethods::PAYMENT_INSTALLMENT0
        );
        return $profileConfig ? $this->getPaymentConfigForProfileAndMethod($profileConfig, $paymentMethod) : null;
    }

    public function getProfileConfig($countryIso, $shopId, $backend = false, $zeroPercentInstallment = false)
    {
        /** @var ProfileConfigRepository $repo */
        $repo = $this->modelManager->getRepository(ProfileConfig::class);
        return $repo->findConfiguration($shopId, $countryIso, $zeroPercentInstallment, $backend);
    }

    public function getPaymentConfigForProfileAndMethod(ProfileConfig $profileConfig, $paymentMethod)
    {
        switch ($paymentMethod) {
            case PaymentMethods::PAYMENT_DEBIT:
                return $profileConfig->getDebitConfig();
            case PaymentMethods::PAYMENT_INVOICE:
                return $profileConfig->getInvoiceConfig();
            case PaymentMethods::PAYMENT_INSTALLMENT0:
            case PaymentMethods::PAYMENT_RATE:
                return $profileConfig->getInstallmentConfig();
            case PaymentMethods::PAYMENT_PREPAYMENT:
                return $profileConfig->getPrepaymentConfig();
        }
        throw new Exception('unknown payment method ' . $paymentMethod);
    }

    /**
     * Parameter must have the following structure:
     * [
     *  [shop_id] => [              // 1 | 2 | 3 | 4 | 5 | ...
     *    [country_code] => [       // de | at | nl | ch | be
     *      [scope] => [            // backend | frontend
     *        [profile_type] => [   // general | installment0
     *          [id] =>             // profile id
     *          [security_code] =>  // security code
     *        ]
     *      ]
     *    ]
     *  ]
     * ]
     *
     * @param array $shopCredentials
     */
    public function refreshProfileConfigs(array $shopCredentials = null)
    {

        if ($shopCredentials == null) {
            $shops = $this->modelManager->getRepository(Shop::class)->findAll();
            foreach ($shops as $shop) {
                foreach ($this->configService->getAllProfileConfigs($shop) as $name => $value) {
                    if (preg_match_all(self::REGEX_CONFIG, $name, $matches)) {
                        $country = $matches[1][0];
                        $scope = $matches[2][0]; // frontend | backend
                        $fieldName = $matches[3][0]; // id | security_code
                        $profileType = $matches[4][0] === 'installment0' ? 'installment0' : 'general';
                        $shopCredentials[$shop->getId()][$country][$scope][$profileType][$fieldName] = trim($value);
                    }
                }
            }
        }

        $this->configWriterService->truncateConfigTables();
        foreach ($shopCredentials as $shopId => $countries) { // de | at | nl | ch | be
            foreach ($countries as $countryCode => $scopes) { // backend | frontend
                foreach ($scopes as $scope => $profileTypes) {  // general | installment0
                    foreach ($profileTypes as $type => $credentials) {
                        if (null !== $credentials['id'] && null !== $credentials['security_code']) {

                            $profileConfig = new ProfileConfig();
                            $profileConfig->setShopId($shopId);
                            $profileConfig->setBackend($scope == 'backend');
                            $profileConfig->setZeroPercentInstallment($type == 'installment0');
                            $profileConfig->setProfileId($credentials['id']);
                            $profileConfig->setSecurityCode($credentials['security_code']);

                            $saveResponse = $this->configWriterService->writeRatepayConfig($profileConfig);

                            if ($saveResponse) {
                                $this->logger->notice('Profile for ' . strtoupper($countryCode) . ' successfully updated.');
                            } else {
                                $errors[] = strtoupper($countryCode) . ' Frontend';
                            }
                        }
                    }
                }
            }
        }
    }
}
