<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\InstallmentCalculator\Service;


use Enlight_Template_Default;
use Enlight_Template_Manager;
use Monolog\Logger;
use RatePAY\Exception\OfflineInstalmentCalculationException;
use RatePAY\Exception\RequestException;
use RatePAY\Model\Request\SubModel\Content\InstallmentCalculation;
use RatePAY\ModelBuilder;
use RatePAY\Service\OfflineInstallmentCalculation;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentBuilder;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentCalculatorContext;
use RpayRatePay\Component\InstallmentCalculator\Model\InstallmentPlanResult;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Exception\NoProfileFoundException;
use RpayRatePay\Exception\RatepayException;
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
        ModelManager             $modelManager,
        ProfileConfigService     $profileConfigService,
        ConfigService            $configService,
        SessionHelper            $sessionHelper,
        Enlight_Template_Manager $templateManager,
        Logger                   $logger
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
     * @throws OfflineInstalmentCalculationException
     */
    public function getInstallmentPlan(InstallmentCalculatorContext $context)
    {
        $installmentBuilders = $this->getInstallmentBuilders($context);
        if (!count($installmentBuilders)) {
            throw new \RuntimeException('InstallmentBuilder can not be created.');
        }

        $matchedBuilder = null;
        $amountBuilders = [];

        foreach ($installmentBuilders as $installmentBuilder) {
            if ($context->getCalculationType() === self::CALCULATION_TYPE_TIME) {
                $matchedBuilder = $installmentBuilder;
                break;
            }

            if ($context->getCalculationType() === self::CALCULATION_TYPE_RATE) {
                // collect all rates for all available plans
                $installmentConfig = $installmentBuilder->getInstallmentPaymentConfig();

                foreach ($installmentConfig->getMonthsAllowed() as $month) {
                    $amountBuilders[$this->getMonthlyRate($context, $installmentConfig, $month)] = $installmentBuilder;
                }
            }
        }

        if ($context->getCalculationType() === self::CALCULATION_TYPE_RATE) {
            // find the best matching for the given monthly rate and the available rates from the calculated plans
            $closestAmount = null;
            $availableMonthlyRates = array_keys($amountBuilders);
            sort($availableMonthlyRates);
            foreach ($availableMonthlyRates as $availableMonthlyRate) {
                if ($closestAmount === null || abs($context->getCalculationValue() - $closestAmount) > abs($availableMonthlyRate - $context->getCalculationValue())) {
                    $closestAmount = $availableMonthlyRate;
                } else if ($availableMonthlyRate > $context->getCalculationValue()) {
                    // if it is not a match, and the calculated rate is already higher than the given value,
                    // we can cancel the loop, cause every higher values will not match, either.
                    break;
                }
            }

            $matchedBuilder = $amountBuilders[$closestAmount];
        }

        if ($matchedBuilder) {
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
     * @return InstallmentBuilder[]
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function getInstallmentBuilders(InstallmentCalculatorContext $context)
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

    public function getProfileConfigs(InstallmentCalculatorContext $context)
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
    public function getInstallmentPlanTemplate($planData)
    {

        /** @var Enlight_Template_Default $template */
        $template = $this->templateManager->createTemplate('frontend/plugins/payment/ratepay/installment/plan.tpl');

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
                if ($this->getMonthlyRate($context, $installmentConfig, $month) >= $installmentConfig->getRateMinNormal()) {
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

    private function getMonthlyRate(InstallmentCalculatorContext $context, ConfigInstallment $config, $month)
    {
        $mbContent = new ModelBuilder('Content');
        $mbContent->setArray([
            'InstallmentCalculation' => [
                'Amount' => $context->getTotalAmount(),
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

    public function getInstallmentCalculatorVars(InstallmentCalculatorContext $context)
    {
        return [
            'translations' => LanguageHelper::getRatepayTranslations(Shopware()->Shop()),
            'calculator' => $this->getInstallmentCalculator($context)
        ];
    }

}
