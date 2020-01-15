<?php

namespace RpayRatePay\Bootstrapping\Events;

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Services\ConfigService;
use RpayRatePay\Services\DfpService;

class TemplateExtensionSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var string
     */
    private $path;

    /** @var ConfigService  */
    protected $configService;

    /** @var DfpService  */
    protected $dfpService;

    /**
     * Shopware_Plugins_Frontend_RpayRatePay_Bootstrapping_Events_TemplateExtensionSubscriber constructor.
     * @param $path string base path to plugin
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->configService = ConfigService::getInstance(); // TODO: if moved to SW5.2 plugin engine: replace!
        $this->dfpService = DfpService::getInstance(); // TODO: if moved to SW5.2 plugin engine: replace!
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'extendTemplates',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function extendTemplates(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $this->registerMyTemplateDir();

        //get ratepay config based on shopId @toDo: IF DI SNIPPET ID WILL BE VARIABLE BETWEEN SUBSHOPS WE NEED TO SELECT BY SHOPID AND COUNTRY CREDENTIALS
        $shopid = Shopware()->Shop()->getId();
        $configPlugin = $this->getRatePayPluginConfig($shopid);

        if (!is_null(Shopware()->Session()->sUserId)) {
            $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
            $paymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
        } elseif (!is_null(Shopware()->Session()->sPaymentID)) { // PaymentId is set in case of new/guest customers
            $paymentMethod = Shopware()->Models()->find('Shopware\Models\Payment\Payment', Shopware()->Session()->sPaymentID);
        } else {
            return;
        }

        if (
            'checkout' === $request->getControllerName() &&
            'confirm' === $request->getActionName() &&
            strstr($paymentMethod->getName(), 'rpayratepay')
        ) {
            $userWrapped = new ShopwareCustomerWrapper($user, Shopware()->Models());
            $phone = $userWrapped->getBilling('phone');
            $view->assign('ratepayPhone', $phone);

            $sandbox = true;
            if ($configPlugin['sandbox'] == 0) {
                $sandbox = false;
            }
            $view->assign('ratepaySandbox', $sandbox);

            $view->extendsTemplate('frontend/payment_rpay_part/index/header.tpl');
            $view->extendsTemplate('frontend/payment_rpay_part/index/index.tpl');
            $view->extendsTemplate('frontend/payment_rpay_part/checkout/confirm.tpl');

            //if no DF token is set, receive all the necessary data to set it and extend template
            if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                // create id and write it to the session
                $view->assign('token', $this->dfpService->getDfpId());
                $view->assign('snippetId', $this->configService->getDfpSnippetId(Shopware()->Shop()));
                $view->extendsTemplate('frontend/payment_rpay_part/index/dfp.tpl');
            }
        }
    }

    /**
     * @param bool $isBackend
     */
    protected function registerMyTemplateDir()
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/responsive', 'rpay');
    }

    /**
     * Get ratepay plugin config from `rpay_ratepay_config`  table
     *
     * @param $shopId
     * @return mixed
     */
    private function getRatePayPluginConfig($shopId)
    {
        //get ratepay config based on shopId
        return Shopware()->Db()->fetchRow(
            'SELECT * FROM `rpay_ratepay_config` WHERE `shopId`=? AND backend=0',
            [$shopId]
        );
    }
}
