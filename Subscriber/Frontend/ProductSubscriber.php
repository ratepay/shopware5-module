<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Monolog\Logger;
use RatePAY\ModelBuilder;
use RatePAY\Service\OfflineInstallmentCalculation;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Service\InstallmentService;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Frontend_Checkout;

class ProductSubscriber implements SubscriberInterface
{

    /**
     * @var InstallmentService
     */
    private $installmentService;
    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        InstallmentService $installmentService,
        ConfigService      $configService,
        SessionHelper      $sessionHelper,
        Logger             $logger
    )
    {
        $this->configService = $configService;
        $this->logger = $logger;
        $this->sessionHelper = $sessionHelper;
        $this->installmentService = $installmentService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail' => 'addOfflineCalculator',
        ];
    }

    public function addOfflineCalculator(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        $productData = $view->getAssign('sArticle');

        /** @noinspection NotOptimalIfConditionsInspection */
        if ($productData === null ||
            $response->isException() ||
            !$view->hasTemplate() ||
            $request->getActionName() !== 'index' ||
            $this->configService->isPicEnabled() === false ||
            (!$productData['isAvailable'] && (!$productData['sConfigurator'] || !$productData['isSelectionSpecified'] || !$productData['hasAvailableVariant']))
        ) {
            return;
        }

        $productPrice = $productData['price_numeric'];

        $paymentConfigSearch = $this->sessionHelper->getPaymentConfigSearchObjectWithoutCustomerData($this->configService->getPicPaymentMethod());
        $paymentConfigSearch->setTotalAmount($productPrice)
            ->setBillingCountry($this->configService->getPicDefaultBillingCountry())
            ->setShippingCountry($this->configService->getPicDefaultShippingCountry())
            ->setNeedsAllowDifferentAddress($paymentConfigSearch->getBillingCountry() !== $paymentConfigSearch->getShippingCountry())
            ->setIsB2b($this->configService->getPicDefaultB2b());

        // this is a little bit hacky, but it just works ;-)
        // we simulate an installment calculation with a requested monthly rate of zero.
        // The service will automatically find the best rate for the request
        $installmentCalculatorContext = (new InstallmentCalculatorContext(
            $paymentConfigSearch,
            $paymentConfigSearch->getTotalAmount(),
            InstallmentService::CALCULATION_TYPE_RATE,
            0
        ))->setUseCheapestRate(true);

        try {
            $result = $this->installmentService->calculateInstallmentOffline($installmentCalculatorContext);
            if ($result) {
                $view->assign([
                    'ratepay' => [
                        'installment' => [
                            'isZeroPercent' => PaymentMethods::isZeroPercentInstallment($this->configService->getPicPaymentMethod()),
                            'rateCount' => $result->getMonthCount(),
                            'monthlyAmount' => $result->getMonthlyRate()
                        ]
                    ]
                ]);
            }
        } catch (NoProfileFoundException $e) {
            // this error is expected. e.g. if the amount is too low for an installment
            // this error will be not logged, cause it will fill up the logfile enormously
        } catch (\Exception $e) {
            $this->logger->error('Error during display of installment information on detail page: ' . $e->getMessage(), [
                'product_id' => $productData['articleID'],
                'product_details_id' => $productData['articleDetailsID'],
                'product_number' => $productData['ordernumber'],
                'product_price' => $installmentCalculatorContext->getTotalAmount(),
                'billing_country' => $installmentCalculatorContext->getPaymentConfigSearch()->getBillingCountry(),
                'shipping_country' => $installmentCalculatorContext->getPaymentConfigSearch()->getShippingCountry(),
                'b2b_enabled' => $installmentCalculatorContext->getPaymentConfigSearch()->isB2b(),
            ]);
        }
    }
}
