<?php

namespace RpayRatePay\Component\Model;



/**
 * Class ShopwareAddressWrapper
 * @package RpayRatePay\Component\Model
 */
class ShopwareAddressWrapper
{
    /** @var object */
    private $address;

    /** @var \Shopware\Components\Model\ModelManager */
    private $em;

    /** @var string */
    private $addressClass;

    /**
     * ShopwareAddressWrapper constructor.
     * @param object $address
     * @param \Shopware\Components\Model\ModelManager $em
     */
    public function __construct($address, $em)
    {
        $this->address = $address;
        $this->em = $em;
        $this->addressClass = get_class($address);
    }

    /**
     * @return \Shopware\Models\Country\Country
     * @throws \Exception
     */
    public function getCountry()
    {
        if (method_exists($this->address, 'getCountry')) {
            return $this->address->getCountry();
        }

        if (method_exists($this->address, 'getCountryId')) {
            return $this->em->find('Shopware\Models\Country\Country', $this->address->getCountryId());
        }

        throw new \Exception('False object type sent to ShopwareAddressWrapper');
    }

}