<?php

namespace RpayRatePay\Bootstrapping;

use RpayRatePay\Component\Service\Logger;

class MenuesSetup extends Bootstrapper
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        try {
            $existing = $this->bootstrap->Menu()->findOneBy([
                'controller' => 'RpayRatepayLogging',
                'action' => 'index',
            ]);

            if($existing) {
                Logger::singleton()->info('DELETING RpayRatepayLogging menu item');
                Shopware()->Db()->delete('s_core_menu', ['id=?' => $existing->getId()]);
            }

            $parent = $this->bootstrap->Menu()->findOneBy(['label' => 'logfile']);
            $this->bootstrap->createMenuItem(
                [
                    'label' => 'RatePAY',
                    'class' => 'sprite-cards-stack',
                    'active' => 1,
                    'controller' => 'RpayRatepayLogging',
                    'action' => 'index',
                    'parent' => $parent
                ]
            );
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new \Exception('Can not create menu entry.' . $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    public function update()
    {
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }
}
