<?php


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
        if ($profileConfig->isSandbox() == null) {
            if (strpos($profileConfig->getProfileId(), '_TE_')) {
                $profileConfig->setSandbox(true);
            } elseif (strpos($profileConfig->getProfileId(), '_PR_')) {
                $profileConfig->setSandbox(false);
            } else {
                $profileConfig->setSandbox(true);
            }
        }
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
