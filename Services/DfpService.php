<?php


namespace RpayRatePay\Services;


use Shopware\Components\DependencyInjection\Container;

/**
 * ServiceClass for device fingerprinting
 * Class DfpService
 * @package RpayRatePay\Services
 */
class DfpService
{

    const SESSION_VAR_NAME = 'dfpToken';

    /** @var Container  */
    protected $container;

    public function __construct()
    {
        $this->container = Shopware()->Container(); //TODO - das muss doch irgendwie via DI reinkommen können... oder?
    }

    public function isDfpIdAlreadyGenerated() {
        return $this->getRatePaySession(self::SESSION_VAR_NAME) !== null;
    }

    public function getDfpId($backend = false) {
        if($backend) {
            return null; //TODO currently it is not supported
        }
        $isStoreFront = $this->container->has('shop');
        if($isStoreFront) {
            if($this->isDfpIdAlreadyGenerated()) {
                return $this->getRatePaySession(self::SESSION_VAR_NAME);
            }
            // storefront request
            $sessionId = $this->getSession()->get('sessionId');
        } else {
            //admin or console request
            $sessionId = rand();
        }

        $token = md5($sessionId . microtime());

        if($isStoreFront) {
            // if it is a storefront request we will safe the token to the session for later access
            // in the admin we only need it once
            $this->setRatePaySession(self::SESSION_VAR_NAME, $token);
        }
        return $token;
    }

    public function deleteDfpId()
    {
        $this->setRatePaySession(self::SESSION_VAR_NAME, null);
    }


    protected function setRatePaySession($key, $value) {
        return $this->getSession()->RatePAY[$key]= $value;
    }

    protected function getRatePaySession($key) {
        $session = $this->getSession()->get('RatePAY');
        return isset($session[$key]) ? $session[$key]: null;
    }

    protected function getSession() {
        if($this->container->has('shop')) {
            return $this->container->get('session');
        }
        throw new \BadMethodCallException('this call is not allowed if you do not have a storefront session');
    }

}
