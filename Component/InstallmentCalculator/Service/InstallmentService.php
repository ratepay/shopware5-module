<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\InstallmentCalculator\Service;


use Enlight_Template_Default;
use Enlight_Template_Manager;
use Monolog\Logger;
use RatePAY\Exception\RequestException;
use RatePAY\ModelBuilder;
use RatePAY\Service\OfflineInstallmentCalculation;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentBuilder;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentPlanResult;
use RpayRatePay\Component\InstallmentCalculator\Model\OfflineInstallmentCalculatorResult;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Helper\LanguageHelper;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

class InstallmentService
{
    const CALCULATION_TYPE_TIME = 'time';
    const CALCULATION_TYPE_RATE = 'rate';

    /**
     * @var ProfileConfigService
     */
    protected $profileConfigService;

    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ModelManager $modelManager,
        ProfileConfigService $profileConfigService,
        ConfigService $configService,
        SessionHelper $sessionHelper,
        Enlight_Template_Manager $templateManager,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->profileConfigService = $profileConfigService;
        $this->configService = $configService;
        $this->sessionHelper = $sessionHelper;
        $this->templateManager = $templateManager;
        $this->logger = $logger;
    }

    /**
     * @param InstallmentCalculatorContext $context
     * @return InstallmentPlanResult
     * @throws \RatePAY\Exception\RequestException
     */
    public function initInstallmentData(InstallmentCalculatorContext $context)
    {
        $planResult = $this->getInstallmentPlan($context);

        $this->sessionHelper->setPlanResult($planResult);
        return $planResult;
    }

    /**
     * @param InstallmentCalculatorContext $context
     * @return InstallmentPlanResult|null
     * @throws RequestException
     */
    public function getInstallmentPlan(InstallmentCalculatorContext $context)
    {
        $result = $this->calculateInstallmentOffline($context);
        if ($matchedBuilder = $result->getBuilder()) {
            try {
                $panJson = $matchedBuilder->getInstallmentPlanAsJson($context->getTotalAmount(), $context->getCalculationType(), $context->getCalculationValue());
                $matchedPlan = json_decode($panJson, true);

                if (is_array($matchedPlan)) {
                    return new InstallmentPlanResult($context, $matchedBuilder, $matchedPlan);
                }
            } catch (RequestException $e) {
                $this->logger->error('Error during fetching installment plan: ' . $e->getMessage(), [
                    'total_amount' => $context->getTotalAmount(),
                    'calculation_type' => $context->getCalculationType(),
                    'calculation_value' => $context->getCalculationValue(),
                    'profile_id' => $matchedBuilder->getProfileConfig()->getProfileId()
                ]);

                throw $e;
            }
        }

        return null;
    }

    /**
     * @param InstallmentCalculatorContext $context
     * @return OfflineInstallmentCalculatorResult
     * @throws NoProfileFoundException
     * @throws RequestException
     */
    public function calculateInstallmentOffline(InstallmentCalculatorContext $context)
    {
        $installmentBuilders = $this->getInstallmentBuilders($context);

        if (!count($installmentBuilders)) {
            throw new NoProfileFoundException();
        }

        /**
         * @var array{0: InstallmentBuilder, 0: int} $amountBuilders
         */
        $amountBuilders = [];

        foreach ($installmentBuilders as $installmentBuilder) {
            $installmentConfig = $installmentBuilder->getInstallmentPaymentConfig();

            if ($context->getCalculationType() === self::CALCULATION_TYPE_TIME) {
                $rate = $this->calculateMonthlyRate($context->getTotalAmount(), $installmentConfig, $context->getCalculationValue());
                if ($rate >= $installmentConfig->getRateMinNormal()) {
                    $amountBuilders[$rate] = [$installmentBuilder, $context->getCalculationValue()];
                    break; // an explicit month was requested and there is a result. It is not required to compare it with other profiles
                }
            }

            if ($context->getCalculationType() === self::CALCULATION_TYPE_RATE) {
                // collect all rates for all available plans
                foreach ($installmentConfig->getMonthsAllowed() as $month) {
                    $rate = $this->calculateMonthlyRate($context->getTotalAmount(), $installmentConfig, $month);
                    if ($rate >= $installmentConfig->getRateMinNormal()) {
                        $amountBuilders[(string)$rate] = [$installmentBuilder, $month];
                        // we will NOT break the parent foreach, cause there might be a better result (of a builder) which is nearer to the requested value than the current.
                    }
                }
            }
        }

        $count = count($amountBuilders);
        if ($count === 0) {
            return null;
        } else if ($count > 1) {
            // find the best matching for the given monthly rate and the available rates from the calculated plans
            $monthlyRate = null;
            $availableMonthlyRates = array_keys($amountBuilders);
            sort($availableMonthlyRates);
            if ($context->isUseCheapestRate()) {
                $monthlyRate = $availableMonthlyRates[0];
            } else {
                foreach ($availableMonthlyRates as $availableMonthlyRate) {
                    if ($monthlyRate === null || abs($context->getCalculationValue() - (float)$monthlyRate) > abs($availableMonthlyRate - $context->getCalculationValue())) {
                        $monthlyRate = $availableMonthlyRate;
                    } else if ($availableMonthlyRate > $context->getCalculationValue()) {
                        // if it is not a match, and the calculated rate is already higher than the given value,
                        // we can cancel the loop, cause every higher values will not match, either.
                        break;
                    }
                }
            }
        } else {
            $monthlyRate = array_key_first($amountBuilders);
        }

        return new OfflineInstallmentCalculatorResult(
            $context,
            $amountBuilders[$monthlyRate][0],
            $amountBuilders[$monthlyRate][1],
            (float)$monthlyRate
        );
    }

    private function calculateMonthlyRate($totalAmount, ConfigInstallment $config, $month)
    {
        $mbContent = new ModelBuilder('Content');
        $mbContent->setArray([
            'InstallmentCalculation' => [
                'Amount' => $totalAmount,
                'PaymentFirstday' => PaymentFirstDay::getFirstDayForPayType($config->getDefaultPaymentType()),
                'InterestRate' => $config->getInterestRateDefault(),
                'ServiceCharge' => $config->getServiceCharge(),
                'CalculationTime' => [
                    'Month' => $month
                ]
            ]
        ]);

        return (new OfflineInstallmentCalculation())->callOfflineCalculation($mbContent)->subtype('calculation-by-time');
    }

    /**
     * @param InstallmentCalculatorContext $context
     * @return InstallmentBuilder[]
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function getInstallmentBuilders(InstallmentCalculatorContext $context)
    {
        $shop = $this->modelManager->find(Shop::class, $context->getPaymentConfigSearch()->getShop());

        return array_map(static function (ProfileConfig $profileConfig) use ($context, $shop) {
            return new InstallmentBuilder(
                $profileConfig,
                strtoupper(substr($shop->getLocale()->getLocale(), 0, 2)),
                $context->getPaymentConfigSearch()->getBillingCountry()
            );
        }, $this->getProfileConfigs($context));
    }

    private function getProfileConfigs(InstallmentCalculatorContext $context)
    {
        $paymentMethodConfigs = $this->profileConfigService->getPaymentConfigurations($context->getPaymentConfigSearch());

        if ($context->getCalculationType() === self::CALCULATION_TYPE_TIME) {
            $modelManager = $this->modelManager;
            $paymentMethodConfigs = array_filter($paymentMethodConfigs, static function (ConfigPayment $config) use ($context, $modelManager) {
                $installmentConfig = $modelManager->find(ConfigInstallment::class, $config->getId());
                return $installmentConfig && in_array($context->getCalculationValue(), $installmentConfig->getMonthsAllowed(), false);
            });
        }

        return array_map(static function (ConfigPayment $configPayment) {
            return $configPayment->getProfileConfig();
        }, $paymentMethodConfigs);
    }

    /**
     * @param array $planData
     * @return string
     * @throws \SmartyException
     */
    public function getInstallmentPlanTemplate($planData, $isPreview = false)
    {
        if ($isPreview) {
            $templateFile = 'frontend/plugins/payment/ratepay/installment/plan_preview.tpl';
        } else {
            $templateFile = 'frontend/plugins/payment/ratepay/installment/plan.tpl';
        }

        /** @var Enlight_Template_Default $template */
        $template = $this->templateManager->createTemplate($templateFile);

        $template->assign('ratepay', [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'plan' => $planData
        ]);

        return $template->fetch();
    }

    public function getInstallmentCalculator(InstallmentCalculatorContext $context)
    {
        $installmentBuilders = $this->getInstallmentBuilders($context);

        if (count($installmentBuilders) === 0) {
            throw new NoProfileFoundException();
        }

        $allowedMonths = [];
        foreach ($installmentBuilders as $installmentBuilder) {
            $installmentConfig = $installmentBuilder->getInstallmentPaymentConfig();
            foreach ($installmentConfig->getMonthsAllowed() as $month) {
                if ($this->calculateMonthlyRate($context->getTotalAmount(), $installmentConfig, $month) >= $installmentConfig->getRateMinNormal()) {
                    $allowedMonths[] = $month;
                }
            }
        }

        $data['rp_allowedMonths'] = $allowedMonths;
        sort($data['rp_allowedMonths']);

        $data['defaults']['type'] = self::CALCULATION_TYPE_TIME;
        $data['defaults']['value'] = $data['rp_allowedMonths'][0];

        return $data;
    }

    public function getInstallmentCalculatorVars(InstallmentCalculatorContext $context)
    {
        return [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'calculator' => $this->getInstallmentCalculator($context)
        ];
    }

}
