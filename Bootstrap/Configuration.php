<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;

use RpayRatePay\Services\PaymentMethodsService;
use Shopware\Models\Config\Element;
use Shopware\Models\Config\Value;

class Configuration extends AbstractBootstrap
{

    public function preInstall()
    {
        $this->moveOldPluginConfiguration();
    }

    public function preUpdate()
    {
        $this->moveOldPluginConfiguration();
    }

    public function install()
    {
        // do nothing
    }

    public function update()
    {
        // do nothing
    }

    public function uninstall($keepUserData = false)
    {
    }

    public function activate()
    {
        // do nothing
    }

    public function deactivate()
    {
        // do nothing
    }

    private function moveOldPluginConfiguration()
    {
        $fieldNames = [
            "RatePayProfileIDDE" => null,
            "RatePaySecurityCodeDE" => null,
            "RatePayProfileIDDEBackend" => null,
            "RatePaySecurityCodeDEBackend" => null,
            "RatePayProfileIDAT" => null,
            "RatePaySecurityCodeAT" => null,
            "RatePayProfileIDATBackend" => null,
            "RatePaySecurityCodeATBackend" => null,
            "RatePayProfileIDCH" => null,
            "RatePaySecurityCodeCH" => null,
            "RatePayProfileIDCHBackend" => null,
            "RatePaySecurityCodeCHBackend" => null,
            "RatePayProfileIDBE" => null,
            "RatePaySecurityCodeBE" => null,
            "RatePayProfileIDBEBackend" => null,
            "RatePaySecurityCodeBEBackend" => null,
            "RatePayProfileIDNL" => null,
            "RatePaySecurityCodeNL" => null,
            "RatePayProfileIDNLBackend" => null,
            "RatePaySecurityCodeNLBackend" => null,
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
        foreach ($oldFields as $oldField) {
            if ($newFieldName = $fieldNames[$oldField->getName()]) {
                /** @var Element $newField */
                $newField = $elementRepo->findOneBy(['name' => $newFieldName]);
                if ($newField && $oldField->getValues()) {
                    /** @var Value $value */
                    foreach ($oldField->getValues() as $value) {
                        if (!empty($value->getValue())) {
                            $value->setElement($newField);
                            $oldField->getValues()->removeElement($value);
                        }
                    }
                }
            }
            $this->modelManager->remove($oldField);
        }
        $this->modelManager->flush();
    }
}
