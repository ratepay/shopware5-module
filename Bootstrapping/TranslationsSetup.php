<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 12.06.18
 * Time: 13:49
 */

class Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_TranslationsSetup extends Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Bootstrapper
{
    private $translations = [];

    /**
     * @throws Exception
     */
    public function install() {
        $availableLocales = ['de_DE','en_EN'];

        try {
            $form = $this->bootstrap->Form();
            $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
            foreach ($availableLocales as $locale) {
                /* @var \Shopware\Models\Shop\Locale */
                $localeModel = $shopRepository->findOneBy(compact('locale'));
                if ($localeModel === null) {
                    continue;
                }

                $snippets = $this->loadConfig('locale/backend/'.$locale.'.json');
                foreach ($snippets as $element => $snippet) {
                    $elementModel = $form->getElement($element);
                    if ($elementModel === null) {
                        continue;
                    }

                    $translationModel = new \Shopware\Models\Config\ElementTranslation();
                    $translationModel->setLabel($snippet);
                    $translationModel->setLocale($localeModel);
                    $elementModel->addTranslation($translationModel);
                }
            }
        } catch (Exception $exception) {
            $this->bootstrap->uninstall();
            throw new Exception("Can not create translation." . $exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update() {
        $this->install();
        $this->languageUpdate();
    }

    /**
     * @return mixed|void
     */
    public function uninstall() {}


    private function languageUpdate()
    {
        $locales = array(
            2 => 'en_EN',
            108 => 'fr_FR',
            176 => 'nl_NL',
        );

        $germanMessages = $this->getInstalledGermanMessages();

        foreach ($germanMessages as $messageName) {
            foreach ($locales AS $locale => $code) {
                $lang = Shopware()->Db()->fetchRow(
                    "SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = " . $locale . " AND `name` = '" . $messageName . "'"
                );

                if (empty($lang)) {
                    $translation = $this->getTranslatedMessage($code, $messageName);
                    if (!empty($translation)) {
                        Shopware()->Db()->insert('s_core_snippets', array(
                            'namespace' => 'RatePay',
                            'localeID' => $locale,
                            'shopID' => 1,
                            'name' => $messageName,
                            'value' => $translation,
                        ));
                    }
                }
            }
        }
    }

    /**
     * @return array
     * @throws Zend_Db_Adapter_Exception
     */
    private function getInstalledGermanMessages()
    {
        $messages = Shopware()->Db()->fetchAssoc(
            "SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = 1"
        );

        return array_map(function($item) {
            return $item['name'];
        }, $messages);
    }

    /**
     * get translations
     *
     * @param $locale
     * @param $name
     * @return mixed
     * @throws Exception
     */
    private function getTranslatedMessage($locale, $name) {
        if (empty($this->translations) || !array_key_exists($locale, $this->translations)) {
            $this->translations[$locale] = $this->loadConfig('locale/frontend/'.$locale.'.json');
        }

        return $this->translations[$locale][$name];
    }
}