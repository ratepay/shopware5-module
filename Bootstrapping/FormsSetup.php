<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping;

class FormsSetup extends Bootstrapper
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        $availableLocales = ['de_DE', 'en_GB'];
        try {
            $translations = [];
            $form = $this->bootstrap->Form();
            $formElements = $this->loadConfig('form_elements.json');

            foreach ($formElements as $element) {
                $form->setElement($element['type'], $element['name'], $element['config']);
            }

            foreach ($availableLocales as $locale) {
                $messages = $this->loadConfig('locale/backend/' . $locale . '.json');
                $translations[$locale] = $messages;
            }

            $this->bootstrap->addFormTranslations($translations);
        } catch (\Exception $exception) {
            $this->bootstrap->uninstall();
            throw new \Exception('Can not create config elements.' . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $this->install();
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }
}
