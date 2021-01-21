<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


use RpayRatePay\Models\ProfileConfig;

class ProfileRequestService extends AbstractRequest
{
    /**
     * @var ProfileConfig
     */
    protected $profileConfig = null;

    /**
     * @param $isBackend
     * @return ProfileConfig
     */
    public function getProfileConfig()
    {
        return $this->profileConfig;
    }

    public function setProfileConfig(ProfileConfig $profileConfig)
    {
        $this->profileConfig = $profileConfig;
    }

    /**
     * @return string
     */
    protected function getCallName()
    {
        return self::CALL_PROFILE_REQUEST;
    }

    /**
     * @return array
     */
    protected function getRequestContent()
    {
    }

    protected function processSuccess()
    {
        // TODO: Implement processSuccess() method.
    }
}
