<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */
namespace Shopware\RatePAY\Bootstrapping;

use Shopware\RatePAY\Bootstrapping\Bootstrapper;

class MenuesSetup extends Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        try {
            $parent = $this->bootstrap->Menu()->findOneBy(array('label' => 'logfile'));
            $this->bootstrap->createMenuItem(array(
                    'label'      => 'RatePAY',
                    'class'      => 'sprite-cards-stack',
                    'active'     => 1,
                    'controller' => 'RpayRatepayLogging',
                    'action'     => 'index',
                    'parent'     => $parent
                )
            );
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception("Can not create menu entry." . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {}

    /**
     * @return mixed|void
     */
    public function uninstall() {}
}