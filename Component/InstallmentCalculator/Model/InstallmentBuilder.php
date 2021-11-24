<?php


namespace RpayRatePay\Component\InstallmentCalculator\Model;


use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;

class InstallmentBuilder extends \RatePAY\Frontend\InstallmentBuilder
{

    /**
     * @var ProfileConfig
     */
    private $profileConfig;

    /** @var ConfigInstallment|null */
    private $_installmentConfig;

    public function __construct(ProfileConfig $profileConfig, $language = 'DE', $country = 'DE')
    {
        $this->profileConfig = $profileConfig;

        parent::__construct(
            $profileConfig->isSandbox(),
            $profileConfig->getProfileId(),
            $profileConfig->getSecurityCode(),
            $language,
            $country
        );
    }

    /**
     * @param string $profileId
     */
    public function setProfileId($profileId)
    {
        if ($this->profileConfig->getProfileId() !== $profileId) {
            throw new \BadMethodCallException('please do not set profile id manually. Please use constructor');
        }

        parent::setProfileId($profileId);
    }

    /**
     * @return ProfileConfig
     */
    public function getProfileConfig()
    {
        return $this->profileConfig;
    }

    /**
     * @return \RpayRatePay\Models\ConfigInstallment
     */
    public function getInstallmentPaymentConfig()
    {
        if (!$this->_installmentConfig) {
            /** @var ConfigPayment|null $paymentConfig */
            $paymentConfig = $this->profileConfig->getPaymentMethodConfigs()->filter(static function (ConfigPayment $paymentConfig) {
                return PaymentMethods::getRatepayPaymentMethod($paymentConfig->getPaymentMethod()) === PaymentMethods::RATEPAY_PAYMENT_INSTALLMENT;
            })->first();

            if ($paymentConfig === null) {
                throw new \RuntimeException('payment config for installment was not found for profile config with profile-id ' . $this->profileConfig->getProfileId());
            }

            $this->_installmentConfig = Shopware()->Models()->find(ConfigInstallment::class, $paymentConfig->getId());

            if ($this->_installmentConfig === null) {
                throw new \RuntimeException('installment config was not found for payment config with id: ' . $paymentConfig->getId());
            }
        }

        return $this->_installmentConfig;
    }
}
