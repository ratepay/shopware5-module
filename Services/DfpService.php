<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;

use Enlight_Components_Session_Namespace;
use RatePAY\Service\DeviceFingerprint;
use RpayRatePay\Helper\SessionHelper;

/**
 * ServiceClass for device fingerprinting
 * Class DfpService
 * @package RpayRatePay\Services
 */
class DfpService
{

    const SESSION_VAR_NAME = 'dfpToken';

    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(SessionHelper $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * @param bool $backend
     * @return string|null
     */
    public function getDfpId($backend = false)
    {
        if ($backend === false) {
            // storefront request
            $sessionValue = $this->sessionHelper->getData(self::SESSION_VAR_NAME);
            if ($sessionValue) {
                return $sessionValue;
            }
            $sessionId = $this->sessionHelper->getSession()->get('sessionId');
        } else {
            // admin or console request
            // $sessionId = rand();
            return null; // RATEPLUG-216: disable DFP Token for admin orders
        }

        $token = DeviceFingerprint::createDeviceIdentToken($sessionId);

        if ($backend === false) {
            // if it is a storefront request we will safe the token to the session for later access
            // in the admin we only need it once
            $this->sessionHelper->setData(self::SESSION_VAR_NAME, $token);
        }
        return $token;
    }

    public function isDfpIdAlreadyGenerated()
    {
        return $this->sessionHelper->getData(self::SESSION_VAR_NAME) !== null;
    }

    public function deleteDfpId()
    {
        $this->sessionHelper->setData(self::SESSION_VAR_NAME, null);
    }

    /**
     * @return Enlight_Components_Session_Namespace
     */
    protected function getSession()
    {
        return $this->sessionHelper->getSession();
    }

}
