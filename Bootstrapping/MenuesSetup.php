<?php

namespace RpayRatePay\Bootstrapping;

class MenuesSetup extends Bootstrapper
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        try {
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
