<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_FormsSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    /**
     * @throws Exception
     */
    public function install() {
        try {
            $form = $this->bootstrap->Form();
            $formElements = $this->loadConfig('form_elements.json');
            foreach ($formElements as $element) {
                $form->setElement($element['type'], $element['name'], $element['config']);
            }
        } catch (Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception("Can not create config elements." . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $this->install();
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}
}