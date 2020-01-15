<?php

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\RatepayConfigWriter;
use RpayRatePay\Models\ProfileConfig;
use Shopware\Models\Shop\Shop;
use RpayRatePay\Services\ConfigService;

class ShopConfigSetup extends Bootstrapper
{
    /**
     * @var ConfigService
     */
    protected $configService;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->configService = ConfigService::getInstance();
    }

    public function install()
    {
        // do nothing
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $configWriter = new RatepayConfigWriter(Shopware()->Db());
        $configWriter->truncateConfigTables();

        //collect configs
        $configs = [];
        $repo = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shops = $repo->findBy(['active' => true]);

        /** @var Shop $shop */
        foreach ($shops as $shop) {
            foreach ($this->configService->getAllConfig($shop) as $key => $value) {

                $matches = [];
                if (preg_match_all('/RatePay(SecurityCode|ProfileID)([aA-zZ]{2})?(Backend)?/', $key, $matches)) {
                    $country = $matches[2][0]; // frontend | backend
                    $fieldName = $matches[1][0]; // profile_id | security_code
                    $backend = $matches[3][0] == 'Backend';
                    $configs[$shop->getId()][$country][$backend][$fieldName] = trim($value);
                }
            }
        }


        //process config
        foreach ($configs as $shopId => $countries) {
            foreach ($countries as $country => $scopes) {
                foreach ($scopes as $isBackend => $config) {
                    if ($this->findDefaultProfile($config['ProfileID'], $country, $isBackend == 1) === null) {
                        $configWriter->writeRatepayConfig($config['ProfileID'], $config['SecurityCode'], $shopId, $isBackend == 1);
                    }
                    if ($country == 'DE' || $country == 'AT') {
                        if ($this->findDefaultProfile($config['ProfileID'] . '_0RT', $country, $isBackend == 1) === null) {
                            $configWriter->writeRatepayConfig($config['ProfileID'] . '_0RT', $config['SecurityCode'], $shopId, $isBackend == 1);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }


    protected function findDefaultProfile($profileId, $country, $isBackend)
    {
        $profileConfigRepo = Shopware()->Models()->getRepository(ProfileConfig::class);
        return $profileConfigRepo->findOneBy([
            'profileId' => $profileId,
            'countryCodeBilling' => $country,
            'backend' => $isBackend == 1
        ]);
    }
}
