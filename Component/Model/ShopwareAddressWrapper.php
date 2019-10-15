<?php

namespace RpayRatePay\Component\Model;

use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;

/**
 * Class ShopwareAddressWrapper
 * @package RpayRatePay\Component\Model
 */
class ShopwareAddressWrapper
{
    /** @var object */
    private $address;

    /** @var ModelManager */
    private $em;

    /** @var string */
    private $addressClass;

    /**
     * ShopwareAddressWrapper constructor.
     * @param object $address
     * @param ModelManager $em
     */
    public function __construct($address, $em)
    {
        $this->address = $address;
        $this->em = $em;
        $this->addressClass = get_class($address);
    }

    /**
     * @return Country
     * @throws Exception
     */
    public function getCountry()
    {
        if (method_exists($this->address, 'getCountry')) {
            return $this->address->getCountry();
        }

        if (method_exists($this->address, 'getCountryId')) {
            return $this->em->find('Shopware\Models\Country\Country', $this->address->getCountryId());
        }

        throw new Exception('False object type sent to ShopwareAddressWrapper');
    }
}
