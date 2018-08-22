<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */
namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\RatepayConfigWriter;

class ShopConfigSetup extends Bootstrapper
{
    public static $AVAILABLE_COUNTRIES = array(
        'DE',
        'AT',
        'CH',
        'NL',
        'BE'
    );

    public function install() {
        // do nothing
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update() {
        $configLoader = new ConfigLoader(Shopware()->Db());
        $configWriter = new RatepayConfigWriter(Shopware()->Db());

        $configWriter->truncateConfigTables();
        Shopware()->Pluginlogger()->info('ShopConfigSetup truncated tables');

        $repo = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shops = $repo->findBy(['active' => true]);

        /** @var \Shopware\Models\Shop\Shop $shop */
        foreach($shops as $shop) {
            $this->updateRatepayConfig($configLoader, $configWriter, $shop->getId(), false);
            $this->updateRatepayConfig($configLoader, $configWriter, $shop->getId(), true);
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}


    public function updateRatepayConfig($configLoader, $configWriter, $shopId, $backend)
    {
        foreach (self::$AVAILABLE_COUNTRIES as $iso) {
            $profileId = $configLoader->getProfileId($iso, $shopId, false, $backend);
            $securityCode = $configLoader->getSecurityCode($iso, $shopId, $backend);

            if(empty($profileId)) {
                continue;
            }

            $configWriter->writeRatepayConfig($profileId, $securityCode, $shopId, $iso, $backend);

            if ($iso == 'DE') {
                $profileIdZeroPercent = $configLoader->getProfileId($iso, $shopId, true, $backend);
                $configWriter->writeRatepayConfig($profileIdZeroPercent, $securityCode, $shopId, $iso, $backend);
            }
        }
    }
}