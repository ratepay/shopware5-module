<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Shop\Shop;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ratepay_profile_config")
 */
class ProfileConfig extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer", length=11, nullable=false)
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="shop_id", type="integer")
     */
    protected $shopId;

    /**
     * @var Shop
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Shop\Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     */
    protected $shop;

    /**
     * @var boolean
     * @ORM\Column(name="backend", type="boolean", nullable=false)
     */
    protected $backend = false;

    /**
     * @var string
     * @ORM\Column(name="profile_id", type="string", length=255, nullable=false)
     */
    protected $profileId;

    /**
     * @var string
     * @ORM\Column(name="security_code", type="string", length=255, nullable=false)
     */
    protected $securityCode;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active = false;

    /**
     * @var array
     * @ORM\Column(name="country_code_billing", type="simple_array", length=2, nullable=true)
     */
    protected $countryCodesBilling;

    /**
     * @var array
     * @ORM\Column(name="country_code_delivery", type="simple_array", length=30, nullable=true)
     */
    protected $countryCodesDelivery;

    /**
     * @var array
     * @ORM\Column(name="currency", type="simple_array", length=30, nullable=true)
     */
    protected $currencies;

    /**
     * @var string
     * @ORM\Column(name="error_default", type="text", length=30, nullable=true, options={"default":"Leider ist eine Bezahlung mit Ratepay nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der <a href='http://www.ratepay.com/legal' target='_blank'>RatePAY-Datenschutzerklärung</a>"})
     */
    protected $errorDefault;

    /**
     * @var boolean
     * @ORM\Column(name="sandbox", type="boolean")
     */
    protected $sandbox = false;

    /**
     * @var ConfigPayment[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="RpayRatePay\Models\ConfigPayment", mappedBy="profileConfig")
     */
    protected $paymentMethodConfigs;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param Shop $shop
     */
    public function setShop(Shop $shop)
    {
        $this->shop = $shop;
        $this->shopId = $shop->getId();
    }

    /**
     * @return Shop
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @return bool
     */
    public function isBackend()
    {
        return $this->backend;
    }

    /**
     * @param bool $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * @return string
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param string $profileId
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @param string $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return array
     */
    public function getCountryCodesBilling()
    {
        return $this->countryCodesBilling;
    }

    /**
     * @param array $countryCodesBilling
     */
    public function setCountryCodesBilling($countryCodesBilling)
    {
        $this->countryCodesBilling = $countryCodesBilling;
    }

    /**
     * @return array
     */
    public function getCountryCodesDelivery()
    {
        return $this->countryCodesDelivery;
    }

    /**
     * @param array $countryCodesDelivery
     */
    public function setCountryCodesDelivery($countryCodesDelivery)
    {
        $this->countryCodesDelivery = $countryCodesDelivery;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @param array $currencies
     */
    public function setCurrencies($currencies)
    {
        $this->currencies = $currencies;
    }

    /**
     * @return string
     */
    public function getErrorDefault()
    {
        return $this->errorDefault;
    }

    /**
     * @param string $errorDefault
     */
    public function setErrorDefault($errorDefault)
    {
        $this->errorDefault = $errorDefault;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->sandbox;
    }

    /**
     * @param bool $sandbox
     */
    public function setSandbox($sandbox)
    {
        $this->sandbox = $sandbox;
    }

    /**
     * @return ConfigPayment[]|ArrayCollection
     */
    public function getPaymentMethodConfigs()
    {
        return $this->paymentMethodConfigs;
    }

}
