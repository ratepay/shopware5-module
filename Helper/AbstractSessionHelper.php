<?php


namespace RpayRatePay\Helper;


use Doctrine\ORM\EntityManager;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Front;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractSessionHelper
{

    /**
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected $isFrontendSession;

    public function __construct(
        ModelManager             $entityManager,
        ContainerInterface       $container,
        Enlight_Controller_Front $front
    )
    {
        $this->entityManager = $entityManager;
        switch ($front->Request()->getParam('module')) {
            case 'frontend':
                $this->session = $container->get('session');
                $this->isFrontendSession = true;
                break;
            case 'backend':
                $this->session = $container->get('backendsession');
                $this->isFrontendSession = false;
                break;
        }
    }

    public function getData($key = null, $default = null)
    {
        $data = is_array($this->session->offsetGet('RatePay')) ? $this->session->offsetGet('RatePay') : [];
        if ($key) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        return $data;
    }

    public function setData($key = null, $value = null)
    {
        if ($key === null) {
            $this->session->offsetUnset('RatePay');
        } else {
            $data = $this->getData();
            if ($value !== null) {
                $data[$key] = $value;
            } else {
                unset($data[$key]);
            }
            $this->session->offsetSet('RatePay', $data);
        }
    }

    public function cleanUp()
    {
        $this->setData(null, null);
    }

    public function getSession()
    {
        return $this->session;
    }

    /**
     * this functions add a value to a array in the session.
     * if the key does not exist in the session, the function will create a new array.
     * if the key already exist in the session and the value is not a array, the existing value will added to a new array.
     * @param $key
     * @param $value
     */
    public function addData($key, $value)
    {
        $data = $this->getData($key, []);
        if (is_array($data) == false) {
            $data = [$data];
        }
        $data[] = $value;
        $this->setData($key, $data);
    }
}
