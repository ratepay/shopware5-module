<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;

use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Config\WriterService;
use RpayRatePay\Services\Logger\RequestLogger;
use RpayRatePay\Services\PaymentMethodsService;
use RpayRatePay\Services\Request\ProfileRequestService;
use Shopware\Models\Config\Element;
use Shopware\Models\Config\Value;

class Configuration extends AbstractBootstrap
{

    /**
     * @var PaymentMethodsService
     */
    protected $paymentMethodsService;
    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;
    /**
     * @var WriterService
     */
    protected $profileConfigWriter;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->paymentMethodsService = new PaymentMethodsService($this->modelManager, $this->installContext->getPlugin()->getName());
        $configService = new ConfigService(
            $this->container,
            $this->container->get('shopware.plugin.cached_config_reader'),
            $this->modelManager,
            $this->installContext->getPlugin()->getName(),
            $this->updateContext ? $this->updateContext->getUpdateVersion() : $this->installContext->getPlugin()->getVersion()
        );
        $this->profileConfigWriter = new WriterService( /// TODO uhhh - that's not soo beautiful
            $this->modelManager,
            new ProfileRequestService(
                $db = $this->container->get('db'),
                $configService,
                new RequestLogger(
                    $configService,
                    $this->modelManager,
                    $this->logger
                )
            ),
            $this->logger
        );
        $this->profileConfigService = new ProfileConfigService($this->modelManager, $configService, $this->profileConfigWriter, $this->logger);
    }

    public function preInstall()
    {
        $this->profileConfigWriter->truncateConfigTables();
        $this->moveOldPluginConfiguration();
    }
    public function preUpdate()
    {
        $this->preInstall();
    }

    public function install()
    {
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function update()
    {
        if ($this->updateContext === null && $this->installContext->getPlugin()->getActive() == false) {
            return;
        }
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function uninstall($keepUserData = false)
    {
        //do nothing
    }

    public function activate()
    {
        $this->profileConfigService->refreshProfileConfigs();
    }

    public function deactivate()
    {
        //do nothing
    }

    private function moveOldPluginConfiguration()
    {
        $fieldNames = [
            "RatePayProfileIDDE" => "ratepay/profile/de/frontend/id",
            "RatePaySecurityCodeDE" => "ratepay/profile/de/frontend/security_code",
            "RatePayProfileIDDEBackend" => "ratepay/profile/de/backend/id",
            "RatePaySecurityCodeDEBackend" => "ratepay/profile/de/backend/security_code",
            "RatePayProfileIDAT" => "ratepay/profile/at/frontend/id",
            "RatePaySecurityCodeAT" => "ratepay/profile/at/frontend/security_code",
            "RatePayProfileIDATBackend" => "ratepay/profile/at/backend/id",
            "RatePaySecurityCodeATBackend" => "ratepay/profile/at/backend/security_code",
            "RatePayProfileIDCH" => "ratepay/profile/ch/frontend/id",
            "RatePaySecurityCodeCH" => "ratepay/profile/ch/frontend/security_code",
            "RatePayProfileIDCHBackend" => "ratepay/profile/ch/backend/id",
            "RatePaySecurityCodeCHBackend" => "ratepay/profile/ch/backend/security_code",
            "RatePayProfileIDBE" => "ratepay/profile/be/frontend/id",
            "RatePaySecurityCodeBE" => "ratepay/profile/be/frontend/security_code",
            "RatePayProfileIDBEBackend" => "ratepay/profile/be/backend/id",
            "RatePaySecurityCodeBEBackend" => "ratepay/profile/be/backend/security_code",
            "RatePayProfileIDNL" => "ratepay/profile/nl/frontend/id",
            "RatePaySecurityCodeNL" => "ratepay/profile/nl/frontend/security_code",
            "RatePayProfileIDNLBackend" => "ratepay/profile/nl/backend/id",
            "RatePaySecurityCodeNLBackend" => "ratepay/profile/nl/backend/security_code",
            "RatePayBidirectional" => "ratepay/bidirectional/enable",
            "RatePayFullDelivery" => "ratepay/bidirectional/status/full_delivery",
            "RatePayFullCancellation" => "ratepay/bidirectional/status/full_cancellation",
            "RatePayFullReturn" => "ratepay/bidirectional/status/full_return",
            "RatePayUseFallbackShippingItem" => "ratepay/advanced/use_fallback_shipping_item",
            "RatePayUseFallbackDiscountItem" => "ratepay/advanced/use_fallback_discount_item",
            "RatePayInvoicePaymentStatus" => "ratepay/status/rpayratepayinvoice",
            "RatePayDebitPaymentStatus" => "ratepay/status/rpayratepaydebit",
            "RatePayInstallmentPaymentStatus" => "ratepay/status/rpayratepayrate",
            "RatePayInstallment0PaymentStatus" => "ratepay/status/rpayratepayrate0",
            "RatePayPrepaidPaymentStatus" => "ratepay/status/rpayratepayprepayment"
        ];

        $elementRepo = $this->modelManager->getRepository(Element::class);
        $oldFields = $elementRepo->findBy(['name' => array_keys($fieldNames)]);

        $this->modelManager->beginTransaction();
        /** @var Element $oldField */
        foreach($oldFields as $oldField) {
            /** @var Element $newField */
            $newField = $elementRepo->findOneBy(['name' => $fieldNames[$oldField->getName()]]);
            if($newField && $oldField->getValues()) {
                /** @var Value $value */
                foreach($oldField->getValues() as $value) {
                    if(!empty($value->getValue())) {
                        $value->setElement($newField);
                        $oldField->getValues()->removeElement($value);
                    }
                }
            }
            $this->modelManager->remove($oldField);
        }
        $this->modelManager->flush();
    }
}
