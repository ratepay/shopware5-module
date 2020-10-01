<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;


use Enlight_Components_Snippet_Manager;
use RpayRatePay\Helper\SessionHelper;

class MessageManager
{

    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var Enlight_Components_Snippet_Manager
     */
    private $snippetManager;

    public function __construct(Enlight_Components_Snippet_Manager $snippetManager, SessionHelper $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
        $this->snippetManager = $snippetManager;
    }

    public function addInfoMessage($key)
    {
        $this->addMessage($key, 'info');
    }

    public function addErrorMessage($key)
    {
        $this->addMessage($key, 'error');
    }

    public function addSuccessMessage($key)
    {
        $this->addMessage($key, 'success');
    }

    protected function addMessage($key, $type)
    {
        $message = $this->getSnippet($key) ? : $key;
        $messages = $this->sessionHelper->getData('messages', []);
        $messages[] = [
            'type' => $type,
            'message' => (string) $message
        ];
        $this->sessionHelper->setData('messages', $messages);
    }

    protected function getSnippet($key)
    {
        return $this->snippetManager->getNamespace('frontend/ratepay/messages')->get($key);
    }

    public function getMessages($cleanUp = true)
    {
        $data = $this->sessionHelper->getData('messages');
        if($cleanUp) {
            $this->sessionHelper->setData('messages', null);
        }
        return $data;
    }

}
