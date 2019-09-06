<?php

namespace RpayRatePay\Component\Model;

use RpayRatePay\Component\Service\Logger;
use Shopware\Models\Customer\Customer;
use RatePAY\Service\Util;

/**
 * Class ShopwareUserWrapper
 *
 * Wraps the shopware customer object and handles deprecated/deleted/new getters.
 *
 * @package RpayRatePay\Component\Model
 */
class ShopwareCustomerWrapper
{
    /** @var Customer $customer */
    private $customer;

    /** @var \Shopware\Components\Model\ModelManager $em */
    private $em;

    /**
     * ShopwareCustomerWrapper constructor.
     * @param Customer $customer
     * @param \Shopware\Components\Model\ModelManager $em
     */
    public function __construct(Customer $customer, $em)
    {
        $this->customer = $customer;
        $this->em = $em;
    }

    /**
     * @param $property
     * @return null|mixed
     */
    public function getShipping($property = null)
    {
        if($shipping = $this->getShippingAddressFromSession()) {
            return $shipping;
        }

        if (is_null($property)) {
            return $this->getShippingChaotic();
        }

        $getter = 'get' . ucfirst($property);

        $shippingFresh = $this->getShippingFresh();

        if (!is_null($shippingFresh)) {
            if (Util::existsAndNotEmpty($shippingFresh, $getter)) {
                return $shippingFresh->$getter();
            }
        }

        $shippingRotten = $this->getShippingRotten();
        if (!is_null($shippingRotten)) {
            if (Util::existsAndNotEmpty($shippingRotten, $getter)) {
                return $shippingRotten->$getter();
            }
        }

        return null;
    }

    /**
     * @param $property
     * @return null|mixed
     */
    public function getBilling($property = null)
    {
        if($billingAddress = $this->getBillingAddressFromSession()) {
            return $billingAddress;
        }

        if (is_null($property)) {
            return $this->getBillingChaotic();
        }

        $getter = 'get' . ucfirst($property);

        $billingFresh = $this->getBillingFresh();
        if (!is_null($billingFresh)) {
            if (Util::existsAndNotEmpty($billingFresh, $getter)) {
                return $billingFresh->$getter();
            }
        }

        $billingRotten = $this->getBillingRotten();
        if (!is_null($billingRotten)) {
            if (Util::existsAndNotEmpty($billingRotten, $getter)) {
                return $billingRotten->$getter();
            }
        }

        return null;
    }

    private function getBillingChaotic()
    {
        $fresh = $this->getBillingFresh();
        if (!is_null($fresh)) {
            return $fresh;
        }

        $rotten = $this->getBillingRotten();
        return $rotten;
    }

    private function getShippingChaotic()
    {
        $fresh = $this->getShippingFresh();
        if (!is_null($fresh)) {
            return $fresh;
        }

        $rotten = $this->getShippingRotten();
        return $rotten;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getBillingCountry()
    {
        if($shipping = $this->getShippingAddressFromSession()) {
            return $shipping->getCountry();
        }

        $billingFresh = $this->getBillingFresh();

        if (!is_null($billingFresh)) {
            return $billingFresh->getCountry();
        }

        $billingRotten = $this->getBillingRotten();

        if (is_null($billingRotten)) {
            return null;
        }

        $country = $this->em->find('Shopware\Models\Country\Country', $billingRotten->getCountryId());

        return $country;
    }

    public function getBillingFirstName()
    {
        $billingFresh = $this->getBillingFresh();

        if (!is_null($billingFresh)) {
            return $billingFresh->getFirstname();
        }

        $billingRotten = $this->getBillingRotten();

        if (is_null($billingRotten)) {
            return null;
        }

        return $billingRotten->getFirstName();
    }

    public function getBillingLastName()
    {
        $billingFresh = $this->getBillingFresh();

        if (!is_null($billingFresh)) {
            return $billingFresh->getLastname();
        }

        $billingRotten = $this->getBillingRotten();

        if (is_null($billingRotten)) {
            return null;
        }

        return $billingRotten->getLastName();
    }

    /**
     * @return null|object|\Shopware\Models\Country\Country
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getShippingCountry()
    {
        $shippingFresh = $this->getShippingFresh();

        if (!is_null($shippingFresh)) {
            return $shippingFresh->getCountry();
        }

        $shippingRotten = $this->getShippingRotten();

        if (is_null($shippingRotten)) {
            return null;
        }

        $country = $this->em->find('Shopware\Models\Country\Country', $shippingRotten->getCountryId());

        return $country;
    }

    /**
     * @return null|\Shopware\Models\Customer\Address
     */
    private function getBillingFresh()
    {
        if (Util::existsAndNotEmpty($this->customer, 'getDefaultBillingAddress')) {
            return $this->customer->getDefaultBillingAddress();
        }

        return null;
    }

    /**
     * @return null|\Shopware\Models\Customer\Billing
     */
    private function getBillingRotten()
    {
        if (Util::existsAndNotEmpty($this->customer, 'getBilling')) {
            return $this->customer->getBilling();
        }

        return null;
    }

    /**
     * @return null|\Shopware\Models\Customer\Address
     */
    private function getShippingFresh()
    {
        if (Util::existsAndNotEmpty($this->customer, 'getDefaultShippingAddress')) {
            return $this->customer->getDefaultShippingAddress();
        }

        return null;
    }

    /**
     * @return null|\Shopware\Models\Customer\Shipping
     */
    private function getShippingRotten()
    {
        if (Util::existsAndNotEmpty($this->customer, 'getShipping')) {
            return $this->customer->getShipping();
        }

        return null;
    }

    /**
     * @return \Shopware\Models\Customer\Address|null
     */
    protected function getShippingAddressFromSession() {
        if($this->isAddressAccessibleViaSession()) {
            $shippingAddressId = Shopware()->Session()->offsetGet('checkoutShippingAddressId');
            if (!empty($shippingAddressId)) {
                return Shopware()->Models()->find('Shopware\Models\Customer\Address', $shippingAddressId);
            }
        }
        return null;
    }

    /**
     * @return \Shopware\Models\Customer\Address|null
     */
    protected function getBillingAddressFromSession() {
        if($this->isAddressAccessibleViaSession()) {
            $billingAddressId = Shopware()->Session()->offsetGet('checkoutBillingAddressId');
            if (!empty($billingAddressId)) {
                return Shopware()->Models()->find('Shopware\Models\Customer\Address', $billingAddressId);
            }
        }
        return null;
    }

    protected function isAddressAccessibleViaSession() {
        //validate if the model does exist and if the user uses the frontend or the administration (session will call the `shop`-service)
        return class_exists('Shopware\Models\Customer\Address') && Shopware()->Container()->has('Shop');
    }
}
