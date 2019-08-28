<?php


namespace Shopware\Plugins\Community\Frontend\RpayRatePay\Services;


use Shopware\Components\DependencyInjection\Container;

/**
 * ServiceClass for device fingerprinting
 * Class DfpService
 * @package Shopware\Plugins\Community\Frontend\RpayRatePay\Services
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
        if($this->container->has('shop')) {
            $ratePaySession = $this->getRatePaySession();
            return isset($ratePaySession[self::SESSION_VAR_NAME]) && $ratePaySession[self::SESSION_VAR_NAME] !== null;
        }
    }

    public function getDfpId() {
        if($this->container->has('shop')) {
            if($this->isDfpIdAlreadyGenerated()) {
                return $this->getRatePaySession()[self::SESSION_VAR_NAME];
            }
            // storefront request
            $sessionId = $this->getSession()->get('sessionId');
            $isAdmin = false;
        } else {
            //admin or console request
            $sessionId = rand();
            $isAdmin = true;
        }

        $token = md5($sessionId . microtime());

        if($isAdmin === false) {
            // if it is a storefront request we will safe the token to the session for later access
            // in the admin we only need it once
            $this->getRatePaySession()[self::SESSION_VAR_NAME] = $token;
        }
        return $token;
    }

    public function deleteDfpId()
    {
        $this->getRatePaySession()[self::SESSION_VAR_NAME] = null;
    }

    /**
     * @return array
     */
    protected function &getRatePaySession() {
        return $this->getSession()->get('RatePAY');
    }

    protected function getSession() {
        if($this->container->has('shop')) {
            return $this->container->get('session');
        }
        throw new \BadMethodCallException('this call is not allowed if you do not have a storefront session');
    }

}
