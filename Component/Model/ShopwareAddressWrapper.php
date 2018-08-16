<?php

namespace RpayRatePay\Component\Model;



/**
 * Class ShopwareAddressWrapper
 * @package RpayRatePay\Component\Model
 */
class ShopwareAddressWrapper
{
    const SHOPWARE_ADDRESS = 'Shopware\Models\Customer\Address';
    const SHOPWARE_SHIPPING = 'Shopware\Models\Customer\Shipping';
    const SHOPWARE_BILLING = 'Shopware\Models\Customer\Billing';

    /** @var object */
    private $address;

    /** @var \Shopware\Components\Model\ModelManager $em */
    private $em;

    /** @var string  */
    private $addressClass;


    /**
     * ShopwareAddressWrapper constructor.
     * @param object $address
     * @param \Shopware\Components\Model\ModelManager $em
     * @throws \Exception
     */
    public function __construct($address, $em)
    {
        $this->address = $address;
        $this->em = $em;
        $this->addressClass = get_class($address);

        if (array_search($this->addressClass, self::getSupportedClasses()) === false) {
            throw new \Exception('Unsupported object type');
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getCountry()
    {
        switch($this->addressClass) {
            case self::SHOPWARE_ADDRESS:
                return $this->address->getCountry();

            case self::SHOPWARE_SHIPPING:
            case self::SHOPWARE_BILLING:
                return $this->em->find('Shopware\Models\Country\Country', $this->address->getCountryId());

            default:
                throw new \Exception('Unsupported object type');
        }
    }

    /**
     * @return array
     */
    private static function getSupportedClasses()
    {
        return [
            self::SHOPWARE_ADDRESS,
            self::SHOPWARE_BILLING,
            self::SHOPWARE_SHIPPING
        ];
    }
}