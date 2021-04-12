<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use RatePAY\Service\DeviceFingerprint;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\DfpService;
use RpayRatePay\Services\StaticTextService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Frontend_Checkout;

class CheckoutSubscriber implements SubscriberInterface
{

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var DfpService
     */
    protected $dfpService;
    /**
     * @var ShopContextInterface
     */
    protected $context;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        ModelManager $modelManager,
        SessionHelper $sessionHelper,
        ConfigService $configService,
        ContextService $contextService,
        DfpService $dfpService
    )
    {
        $this->modelManager = $modelManager;
        $this->context = $contextService->getContext();
        $this->dfpService = $dfpService;
        $this->sessionHelper = $sessionHelper;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'extendTemplates',
        ];
    }

    public function extendTemplates(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if ($response->isException() || !$view->hasTemplate()) {
            return;
        }

        if ($request->getActionName() === 'confirm') {
            $paymentMethod = $this->sessionHelper->getPaymentMethod();
            if (PaymentMethods::exists($paymentMethod) == false) {
                return;
            }
            $data = [];

            if ($this->dfpService->isDfpIdAlreadyGenerated() == false) {
                $dfpHelper = new DeviceFingerprint($this->configService->getDfpSnippetId(Shopware()->Shop()));
                $data['dfp'] = str_replace('\\"', '"', $dfpHelper->getDeviceIdentSnippet($this->dfpService->getDfpId()));
            }

            $view->assign('ratepay', $data);
        }
    }
}
