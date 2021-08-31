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
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Frontend_Checkout;

class ProductSubscriber implements SubscriberInterface
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    /**
     * @var \RpayRatePay\Services\Config\ConfigService
     */
    private $configService;
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    public function __construct(ModelManager $modelManager, ConfigService $configService, Logger $logger)
    {
        $this->modelManager = $modelManager;
        $this->configService = $configService;
        $this->logger = $logger;
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

        $calculator = new OfflineInstallmentCalculation();

        $shop = Shopware()->Shop();

        $profileRepo = $this->modelManager->getRepository(ConfigPayment::class);
        /** @var ConfigPayment|null $paymentConfig */
        $paymentConfig = $profileRepo->findPaymentMethodConfiguration((new PaymentConfigSearch())
            ->setBackend(false)
            ->setPaymentMethod($this->configService->getPicPaymentMethod())
            ->setBillingCountry($this->configService->getPicDefaultBillingCountry())
            ->setCurrency($shop->getCurrency()->getCurrency())
            ->setShippingCountry($this->configService->getPicDefaultShippingCountry())
            ->setShop($shop)
        );

        if ($paymentConfig === null || $paymentConfig->getLimitMin() > $productPrice || $paymentConfig->getLimitMax() < $productPrice) {
            return;
        }

        $installmentConfigRepo = $this->modelManager->getRepository(ConfigInstallment::class);
        $installmentConfig = $installmentConfigRepo->findOneByPaymentConfig($paymentConfig);

        if ($installmentConfig === null) {
            return;
        }

        $monthsAllowed = $installmentConfig->getMonthsAllowed();
        sort($monthsAllowed);

        while (count($monthsAllowed) > 0) {
            $calculationTime = end($monthsAllowed);
            array_pop($monthsAllowed);

            $mbContent = new ModelBuilder('Content');
            $requestData = [
                'InstallmentCalculation' => [
                    'Amount' => $productPrice,
                    'PaymentFirstday' => $installmentConfig->isDebitAllowed() ? 2 : 28,
                    'InterestRate' => $installmentConfig->getInterestRateDefault(),
                    'ServiceCharge' => $installmentConfig->getServiceCharge(),
                    'CalculationTime' => [
                        'Month' => $calculationTime
                    ]
                ]
            ];
            $mbContent->setArray($requestData);

            try {
                $calculationMonthlyRate = $calculator->callOfflineCalculation($mbContent)->subtype("calculation-by-time");
                if ($calculationMonthlyRate > 0 && $installmentConfig->getRateMinNormal() < $calculationMonthlyRate) {
                    $view->assign([
                        'ratepay' => [
                            'installment' => [
                                'isZeroPercent' => PaymentMethods::isZeroPercentInstallment($this->configService->getPicPaymentMethod()),
                                'rateCount' => $calculationTime,
                                'monthlyAmount' => $calculationMonthlyRate
                            ]
                        ]
                    ]);
                    break;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error during display of installment information on detail page: ' . $e->getMessage(), [
                    'product_id' => $productData['articleID'],
                    'product_details_id' => $productData['articleDetailsID'],
                    'product_number' => $productData['ordernumber'],
                    'request_data' => $requestData
                ]);
            }
        }
    }
}
