<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;

class Shopware_Plugins_Frontend_RpayRatePay_Component_Validation
{
    /**
     * An Instance of the Shopware-CustomerModel
     *
     * @var Shopware\Models\Customer\Customer
     */
    private $_user;

    /**
     * @var ShopwareCustomerWrapper
     */
    private $userWrapped;

    /**
     * An Instance of the Shopware-PaymentModel
     *
     * @var Shopware\Models\Payment\Payment
     */
    private $_payment;

    /**
     * Allowed currencies from configuration table
     *
     * @var array
     */
    private $_allowedCurrencies;

    /**
     * Allowed billing countries from configuration table
     *
     * @var array
     */
    private $_allowedCountriesBilling;

    /**
     * Allowed shipping countries from configuration table
     *
     * @var array
     */
    private $_allowedCountriesDelivery;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Component_Validation constructor.
     * @param $user
     * @param null $payment
     * @param bool $backend
     */
    public function __construct($user, $payment = null, $backend = false)
    {
        $this->_user = $user;
        $this->userWrapped = new ShopwareCustomerWrapper($user, Shopware()->Models());
        $this->_payment = $payment;
    }

    /**
     * Sets array with allowed currencies
     *
     * @param $currencyStr
     */
    public function setAllowedCurrencies($currenciesStr)
    {
        $this->_allowedCurrencies = explode(',', $currenciesStr);
    }

    /**
     * Sets array with allowed currencies
     *
     * @param $currencyStr
     */
    public function setAllowedCountriesBilling($countriesStr)
    {
        $this->_allowedCountriesBilling = explode(',', $countriesStr);
    }

    /**
     * Sets array with allowed currencies
     *
     * @param $currencyStr
     */
    public function setAllowedCountriesDelivery($countriesStr)
    {
        $this->_allowedCountriesDelivery = explode(',', $countriesStr);
    }

    /**
     * @param $paymentName
     * @return bool
     */
    public function isRatePAYPayment()
    {
        return in_array($this->_payment->getName(), ['rpayratepayinvoice', 'rpayratepayrate', 'rpayratepaydebit', 'rpayratepayrate0', 'rpayratepayprepayment']);
    }

    /**
     * Checks the Customers Age for RatePAY payments
     *
     * TODO remove duplicate code (see isBirthdayValid)
     * @return boolean
     */
    public function isAgeValid()
    {
        $today = new \DateTime('now');

        $birthday = $this->_user->getBirthday();
        if (empty($birthday) || is_null($birthday)) {
            $birthday = $this->userWrapped->getBilling('birthday');
        }

        $return = false;
        if (!is_null($birthday)) {
            if ($birthday->diff($today)->y >= 18 && $birthday->diff($today)->y <= 120) {
                $return = true;
            }
        }

        return $return;
    }

    /**
     * Checks the format of the birthday-value
     *
     * @return boolean
     */
    public function isBirthdayValid()
    {
        return ValidationService::isBirthdayValid($this->_user);
    }

    /**
     * Checks if the telephoneNumber is Set
     *
     * @return boolean
     */
    public function isTelephoneNumberSet()
    {
        return ValidationService::isTelephoneNumberSet($this->_user);
    }

    /**
     * Checks if the CompanyName is set
     *
     * @return boolean
     */
    public function isCompanyNameSet()
    {
        if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 session contains current billing address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $checkoutAddressBilling = $addressModel->findOneBy(['id' => Shopware()->Session()->checkoutBillingAddressId]);
            $companyName = $checkoutAddressBilling->getCompany();
        } else {
            $companyName = $this->userWrapped->getBilling('company');
        }

        return !empty($companyName);
    }

    /**
     * Compares the Data of billing and shipping addresses.
     *
     * @return boolean
     */
    public function isBillingAddressSameLikeShippingAddress()
    {
        if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 session contains current billing address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $billingAddress = $addressModel->findOneBy(['id' => Shopware()->Session()->checkoutBillingAddressId]);
        } else {
            $billingAddress = $this->userWrapped->getBilling();
        }

        if (Shopware()->Session()->checkoutShippingAddressId > 0) { // From Shopware 5.2 session contains current shipping address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $shippingAddress = $addressModel->findOneBy(['id' => Shopware()->Session()->checkoutShippingAddressId]);
        } else {
            $shippingAddress = $this->userWrapped->getShipping();
        }

        $return = ValidationService::areBillingAndShippingSame($billingAddress, $shippingAddress);

        return $return;
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function isCountryEqual()
    {
        $billingCountry = $this->userWrapped->getBillingCountry();
        $shippingCountry = $this->userWrapped->getShippingCountry();

        if (!is_null($shippingCountry)) {
            if ($billingCountry->getId() != $shippingCountry->getId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Searches allowedCurrencies
     * @param $currency
     * @return bool
     */
    public function isCurrencyValid($currency)
    {
        return array_search($currency, $this->_allowedCurrencies, true) !== false;
    }

    /**
     * Checks if the billing country valid
     *
     * @param Shopware\Models\Country\Country $country
     * @return boolean
     */
    public function isBillingCountryValid($country)
    {
        return array_search($country->getIso(), $this->_allowedCountriesBilling, true) !== false;
    }

    /**
     * Checks if the delivery country valid
     *
     * @param Shopware\Models\Country\Country $country
     * @return boolean
     */
    public function isDeliveryCountryValid($country)
    {
        return array_search($country->getIso(), $this->_allowedCountriesDelivery, true) !== false;
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function isRatepayHidden()
    {
        $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
        $country = $this->userWrapped->getBillingCountry()->getIso();

        if ('DE' === $country || 'AT' === $country || 'CH' === $country) {
            $sandbox = $config->get('RatePaySandbox' . $country);
        }

        if (true === Shopware()->Session()->RatePAY['hidePayment'] && false === $sandbox) {
            return true;
        } else {
            return false;
        }
    }

    public function getUser()
    {
        return $this->_user;
    }
}
