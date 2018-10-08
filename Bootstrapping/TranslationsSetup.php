<?php

namespace RpayRatePay\Bootstrapping;

class TranslationsSetup extends Bootstrapper
{
    private $translations = [];

    /**
     * @throws \Exception
     */
    public function install()
    {
        $this->languageUpdate();
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function update()
    {
        $this->languageUpdate();
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }

    private function languageUpdate()
    {
        $locales = [
            2 => 'en_EN',
            108 => 'fr_FR',
            176 => 'nl_NL',
        ];

        $germanMessages = $this->getInstalledGermanMessages();

        foreach ($germanMessages as $messageName) {
            foreach ($locales as $locale => $code) {
                $lang = Shopware()->Db()->fetchRow(
                    "SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = " . $locale . " AND `name` = '" . $messageName . "'"
                );

                if (empty($lang)) {
                    $translation = $this->getTranslatedMessage($code, $messageName);
                    if (!empty($translation)) {
                        Shopware()->Db()->insert('s_core_snippets', [
                            'namespace' => 'RatePay',
                            'localeID' => $locale,
                            'shopID' => 1,
                            'name' => $messageName,
                            'value' => $translation,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @return array
     * @throws \Zend_Db_Adapter_Exception
     */
    private function getInstalledGermanMessages()
    {
        $messages = Shopware()->Db()->fetchAssoc(
            "SELECT `name` FROM `s_core_snippets` WHERE `namespace` LIKE 'RatePay' AND `localeID` = 1"
        );

        return array_map(function ($item) {
            return $item['name'];
        }, $messages);
    }

    /**
     * get translations
     *
     * @param $locale
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    private function getTranslatedMessage($locale, $name)
    {
        if (empty($this->translations) || !array_key_exists($locale, $this->translations)) {
            $this->translations[$locale] = $this->loadConfig('locale/frontend/' . $locale . '.json');
        }

        return $this->translations[$locale][$name];
    }
}
