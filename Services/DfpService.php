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

    //TODO remove if plugin is moved to SW5.2 plugin engine
    private static $instance = null;
    public static function getInstance(){
        return self::$instance = (self::$instance ? : new self(Shopware()->Container()));
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function isDfpIdAlreadyGenerated() {
        return $this->getRatePaySession(self::SESSION_VAR_NAME) !== null;
    }

    public function getDfpId() {
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
