<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:53
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_PaymentFilterSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var
     */
    protected $_object;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'filterPayments',
        ];
    }

    /**
     * Filters the shown Payments
     * RatePAY-payments will be hidden, if one of the following requirement is not given
     *  - Delivery Address is not allowed to be not the same as billing address
     *  - The Customer must be over 18 years old
     *  - The Country must be germany or austria
     *  - The Currency must be EUR
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return array|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function filterPayments(Enlight_Event_EventArgs $arguments)
    {

        $return = $arguments->getReturn();
        $currency = Shopware()->Config()->get('currency');
        $userId = Shopware()->Session()->sUserId;

        if (empty($userId) || empty($currency)) {
            return;
        }

        $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);

        //get country of order
        if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 find current address information in default billing address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $customerAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutBillingAddressId));

            if ($this->existsAndNotEmpty('getCountry', $customerAddressBilling)) {
                $countryBilling = $customerAddressBilling->getCountry();
            } else {
                if ($this->existsAndNotEmpty('getCountryId', $user->getBilling())) {
                    $countryBilling = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId());
                }
            }

            if (Shopware()->Session()->checkoutShippingAddressId > 0 && Shopware()->Session()->checkoutShippingAddressId != Shopware()->Session()->checkoutBillingAddressId) {
                $customerAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutShippingAddressId));

                if ($this->existsAndNotEmpty('getCountry', $customerAddressShipping)) {
                    $countryDelivery = $customerAddressShipping->getCountry();
                } else {
                    if ($this->existsAndNotEmpty('getCountryId', $user->getShipping())) {
                        $countryDelivery = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getShipping()->getCountryId());
                    }
                }

            } else {
                $countryDelivery = $countryBilling;
            }
        } else {
            $countryBilling = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getBilling()->getCountryId());
            if (!is_null($user->getShipping()) &&$user->getBilling()->getCountryId() != $user->getShipping()->getCountryId()) {
                $countryDelivery = Shopware()->Models()->find('Shopware\Models\Country\Country', $user->getShipping()->getCountryId());
            } else {
                $countryDelivery = $countryBilling;
            }
        }

        //get current shopId
        $shopId = Shopware()->Shop()->getId();

        $config = $this->getRatePayPluginConfigByCountry($shopId, $countryBilling);
        foreach ($config AS $payment => $data) {
            $show[$payment] = $data['status'] == 2 ? true : false;

            $validation = new Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($config);
            $validation->setAllowedCurrencies($data['currency']);
            $validation->setAllowedCountriesBilling($data['country-code-billing']);
            $validation->setAllowedCountriesDelivery($data['country-code-delivery']);

            if ($validation->isRatepayHidden()) {
                $show[$payment] = false;
            }

            if (!$validation->isCurrencyValid($currency)) {
                $show[$payment] = false;
            }

            if (!$validation->isBillingCountryValid($countryBilling)) {
                $show[$payment] = false;
            }

            if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                $show[$payment] = false;
            }

            if ($validation->isCompanyNameSet()) {
                $show[$payment] = $data['b2b'] == '1' && $show[$payment] ? true : false;
                $data['limit_max'] = ($data['limit_max_b2b'] > 0) ? $data['limit_max_b2b'] : $data['limit_max'];
            }

            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                $show[$payment] = (bool) $data['address'] && $show[$payment] ? true : false;
            }

            if (Shopware()->Modules()->Basket()) {
                $basket = Shopware()->Modules()->Basket()->sGetAmount();
                $basket = $basket['totalAmount'];

                Shopware()->Pluginlogger()->info('BasketAmount: ' . $basket);

                if ($basket < $data['limit_min'] || $basket > $data['limit_max']) {
                    $show[$payment] = false;
                }
            }
        }

        $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
        $setToDefaultPayment = false;

        $payments = array();
        foreach ($return as $payment) {
            if ($payment['name'] === 'rpayratepayinvoice' && !$show['invoice']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Invoice');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayinvoice" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepaydebit' && !$show['debit']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Debit');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepaydebit" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepayrate' && !$show['installment']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepayrate0' && !$show['installment0']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate0');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate0" ? : $setToDefaultPayment;
                continue;
            }
            $payments[] = $payment;
        }

        if ($setToDefaultPayment) {
            $user->setPaymentId(Shopware()->Config()->get('paymentdefault'));
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush();
            Shopware()->Models()->refresh($user);
        }

        return $payments;
    }

    /**
     * Get ratepay plugin config from rpay_ratepay_config table
     *
     * @param $shopId
     * @param $country
     * @return array
     */
    private function getRatePayPluginConfigByCountry($shopId, $country) {
        //fetch correct config for current shop based on user country
        $profileId = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config()->get('RatePayProfileID' . $country->getIso());
        $payments = array("installment", "invoice", "debit", "installment0");
        $paymentConfig = array();

        foreach ($payments AS $payment) {
            $qry = "SELECT * 
                        FROM `rpay_ratepay_config` AS rrc
                          JOIN `rpay_ratepay_config_payment` AS rrcp
                            ON rrcp.`rpay_id` = rrc.`" . $payment . "`
                          LEFT JOIN `rpay_ratepay_config_installment` AS rrci
                            ON rrci.`rpay_id` = rrc.`" . $payment . "`
                        WHERE rrc.`shopId` = '" . $shopId . "'
                             AND rrc.`profileId`= '" . $profileId . "'";
            $result = Shopware()->Db()->fetchRow($qry);

            if (!empty($result)) {
                $paymentConfig[$payment] = $result;
            }
        }

        return $paymentConfig;
    }

    /**
     * @param $method
     * @param $object
     * @return bool
     */
    private function existsAndNotEmpty($method, $object) {
        if (method_exists($object, $method)) {
            $var = $object->$method();
            if (!empty($var) && !is_null($var)) {
                return true;
            }
        }
        return false;
    }
}