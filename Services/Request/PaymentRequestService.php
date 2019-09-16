<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RatePAY\Service\Util;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Service\SessionLoader;
use RpayRatePay\Component\Service\ShopwareUtil;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Factory\BasketArrayFactory;
use RpayRatePay\Services\Factory\CustomerArrayFactory;
use RpayRatePay\Services\Factory\PaymentArrayFactory;
use RpayRatePay\Services\Logger\RequestLogger;

class PaymentRequestService extends AbstractRequest
{

    /**
     * @var CustomerArrayFactory
     */
    protected $customerArrayFactory;
    /**
     * @var PaymentArrayFactory
     */
    protected $paymentArrayFactory;

    /**
     * @var PaymentRequestData
     */
    protected $paymentRequestData;

    /**
     * @var boolean
     */
    protected $isBackend;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger,
        CustomerArrayFactory $customerArrayFactory,
        PaymentArrayFactory $paymentArrayFactory
    )
    {
        parent::__construct($db, $configService, $profileConfigService, $requestLogger);
        $this->customerArrayFactory = $customerArrayFactory;
        $this->paymentArrayFactory = $paymentArrayFactory;
    }

    protected function getCallName()
    {
        return 'paymentRequest';
    }

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
        $data = parent::getRequestHead($profileConfig);
        $data['External'] = [
            'OrderId' => null, //TODO currently not transmitted
            'MerchantConsumerId' => $this->paymentRequestData->getCustomer()->getNumber()
        ];
        if($this->paymentRequestData->getDfpToken()) {
            $data['CustomerDevice']['DeviceToken'] = $this->paymentRequestData->getDfpToken();
        }
        return $data;
    }

    protected function getRequestContent()
    {
        if($this->paymentRequestData == null) {
            throw new \RuntimeException('please set paymentRequestData with function `setPaymentRequestData()`');
        }

        $basketFactory = new BasketArrayBuilder($this->paymentRequestData);
        $shoppingBasket = $basketFactory->toArray();

        $data = [
            CustomerArrayFactory::ARRAY_KEY => $this->customerArrayFactory->getData($this->paymentRequestData),
            BasketArrayFactory::ARRAY_KEY => $shoppingBasket,
            PaymentArrayFactory::ARRAY_KEY => $this->paymentArrayFactory->getData($this->paymentRequestData)
        ];

        return $data;
    }

    protected function getProfileConfig()
    {
        return $this->profileConfigService->getProfileConfig(
            $this->paymentRequestData->getBillingAddress()->getCountry()->getIso(),
            $this->paymentRequestData->getShop()->getId(),
            $this->isBackend,
            $this->paymentRequestData->getMethod()->getName() == PaymentMethods::PAYMENT_INSTALLMENT0
        );
    }

    /**
     * @param bool $isBackend
     */
    public function setIsBackend($isBackend)
    {
        $this->isBackend = $isBackend;
    }

    /**
     * @param PaymentRequestData $paymentRequestData
     */
    public function setPaymentRequestData($paymentRequestData)
    {
        $this->paymentRequestData = $paymentRequestData;
    }
}
