<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Config;


use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Monolog\Logger;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\PaymentConfigRepository;
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
     * @param PaymentConfigSearch $configSearch
     * @return object|ConfigInstallment|null
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getInstallmentConfig(PaymentConfigSearch $configSearch)
    {
        $config = $this->getPaymentConfiguration($configSearch);

        return $config ? $this->modelManager->find(ConfigInstallment::class, $config->getId()) : null;
    }

    /**
     * @param PaymentConfigSearch $configSearch
     * @return ConfigPayment|null
     */
    public function getPaymentConfiguration(PaymentConfigSearch $configSearch)
    {
        /** @var PaymentConfigRepository $repo */
        $repo = $this->modelManager->getRepository(ConfigPayment::class);

        return $repo->findPaymentMethodConfiguration($configSearch);
    }

    /**
     * @param int|null $profileEntityId
     * @return bool
     * @throws EntityNotFoundException
     */
    public function refreshProfileConfig($profileEntityId = null)
    {
        if ($profileEntityId === null) {
            $configs = $this->modelManager->getRepository(ProfileConfig::class)->findAll();
            foreach ($configs as $config) {
                $this->refreshProfileConfig($config);
            }
            return true;
        }

        /** @var ProfileConfig $profileConfig */
        $profileConfig = $this->modelManager->find(ProfileConfig::class, $profileEntityId);
        if ($profileConfig === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(ProfileConfig::class, [$profileEntityId]);
        }

        $saveResponse = $this->configWriterService->writeRatepayConfig($profileConfig);
        if ($saveResponse) {
            $this->logger->notice('Profile ' . strtoupper($profileConfig->getProfileId()) . ' successfully refreshed.');
            return true;
        }
        return false;
    }

    /**
     * @param string $profileId the profileId of the entity. NOT the id of the entity!
     * @return ProfileConfig|null
     */
    public function getProfileConfigById($profileId)
    {
        return $this->modelManager->getRepository(ProfileConfig::class)->findOneBy(['profileId' => $profileId]);
    }

}
