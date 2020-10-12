<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\RatepayHelper;
use RpayRatePay\Component\Service\ShopwareUtil;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;
use RpayRatePay\Component\Service\Logger;
use RpayRatePay\Services\PaymentMethodsService;
use RpayRatePay\Services\StaticTextService;
use Shopware_Controllers_Frontend_Checkout;

class PaymentFilterSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var
     */
    protected $_object;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'filterPayments',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'addLegalText'
        ];
    }

    public function addLegalText(\Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $ratepay = $subject->View()->getAssign('ratepay') ? : [];
        $ratepay['legalText'] = StaticTextService::getInstance()->getText('LegalText');
        $subject->View()->assign('ratepay', $ratepay);
    }

    /**
     * Filters the shown Payments
     * Ratepay-payments will be hidden, if one of the following requirement is not given
     *  - Delivery Address is not allowed to be not the same as billing address
     *  - The Customer must be over 18 years old
     *  - The Country must be germany or austria
     *  - The Currency must be EUR
     *
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function filterPayments(\Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();
        $currency = Shopware()->Config()->get('currency');
        $userId = Shopware()->Session()->sUserId;
        if (empty($userId) || empty($currency)) {
            return;
        }

        if (Shopware()->Front()->Request()->getControllerName() !== 'checkout') {
            // only filter the payments if the customer is in the checkout process
            return;
        }

        /** @var \Shopware\Models\Customer\Customer $user */
        $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $userId);
        $wrappedUser = new ShopwareCustomerWrapper($user, Shopware()->Models());

        //get country of order
        if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 find current address information in default billing address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $customerAddressBilling = $addressModel->findOneBy(['id' => Shopware()->Session()->checkoutBillingAddressId]);

            $countryBilling = $customerAddressBilling->getCountry();

            if (Shopware()->Session()->checkoutShippingAddressId > 0 && Shopware()->Session()->checkoutShippingAddressId != Shopware()->Session()->checkoutBillingAddressId) {
                $customerAddressShipping = $addressModel->findOneBy(['id' => Shopware()->Session()->checkoutShippingAddressId]);
                $countryDelivery = $customerAddressShipping->getCountry();
            } else {
                $countryDelivery = $countryBilling;
            }
        } else {
            $countryBilling = $wrappedUser->getBillingCountry();
            $countryDelivery = $wrappedUser->getShippingCountry();

            if (is_null($countryDelivery)) {
                $countryDelivery = $countryBilling;
            }
        }

        //get current shopId
        $shopId = Shopware()->Shop()->getId();

        $show = [];
        $backend = false;
        $config = $this->getRatePayPluginConfigByCountry($shopId, $countryBilling, $backend);
        foreach ($config as $payment => $data) {
            if($data['status'] != 2) {
                continue;
            }

            if(PaymentMethodsService::getInstance()->isPaymentMethodLockedForCustomer($user, $payment)) {
                // the payment method is locked for the customer
                continue;
            }

            $validation = $this->getValidator($user);

            $validation->setAllowedCurrencies($data['currency']);
            $validation->setAllowedCountriesBilling($data['country_code_billing']);
            $validation->setAllowedCountriesDelivery($data['country_code_delivery']);

            if ($validation->isRatepayHidden()) {
                continue;
            }

            if (!$validation->isCurrencyValid($currency)) {
                continue;
            }

            if (!$validation->isBillingCountryValid($countryBilling)) {
                continue;
            }

            if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                continue;
            }

            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                if (!$data['address']) {
                    continue;
                }
            }

            if (Shopware()->Modules()->Basket()) {
                $basket = Shopware()->Modules()->Basket()->sGetAmount();
                $basket = $basket['totalAmount']; //is this always brutto?

                Logger::singleton()->info('BasketAmount: ' . $basket);
                $isB2b = $validation->isCompanyNameSet();

                if (!ValidationService::areAmountsValid($isB2b, $data, $basket)) {
                    continue;
                }
                $show[$payment] = true;
            }
        }

        $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
        $setToDefaultPayment = RatepayHelper::isRatePayPayment($paymentModel) === true && ($paymentModel === null || !isset($show[$paymentModel->getName()]));

        foreach ($return as $i => $payment) {
            if(!isset($show[$payment['name']])) {
                unset($return[$i]);
            }
        }

        if ($setToDefaultPayment) {
            $user->setPaymentId(Shopware()->Config()->get('paymentdefault'));
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush();
            Shopware()->Models()->refresh($user);
        }

        return $return;
    }

    private function getValidator($user)
    {
        return new \Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($user);
    }

    /**
     * Get ratepay plugin config from rpay_ratepay_config table
     *
     * @param $shopId
     * @param $country
     * @param bool $backend
     * @return array
     */
    private function getRatePayPluginConfigByCountry($shopId, $country, $backend = false)
    {
        $configLoader = new ConfigLoader(Shopware()->Db());

        $paymentConfig = [];

        foreach (RatepayHelper::getPaymentMethods() as $paymentMethod) {
            $result = $configLoader->getPluginConfigForPaymentType($shopId, $country->getIso(), ShopwareUtil::getPaymentMethod($paymentMethod), $backend);

            if (!empty($result)) {
                $paymentConfig[$paymentMethod] = $result;
            }
        }

        return $paymentConfig;
    }
}
