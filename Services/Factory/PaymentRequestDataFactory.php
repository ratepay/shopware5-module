<?php


namespace RpayRatePay\Services\Factory;


use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\DTO\BankData;
use RpayRatePay\DTO\InstallmentDetails;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Enum\PaymentSubType;
use RpayRatePay\Helper\SessionHelper;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Services\DfpService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;

class PaymentRequestDataFactory
{

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var DfpService
     */
    protected $dfpService;
    /**
     * @var SessionHelper
     */
    private $sessionHelper;

    public function __construct(
        ModelManager $modelManager,
        DfpService $dfpService,
        SessionHelper $sessionHelper
    )
    {
        $this->modelManager = $modelManager;
        $this->dfpService = $dfpService;
        $this->sessionHelper = $sessionHelper;
    }

    public function createFromOrderStruct(OrderStruct $orderStruct, array $loadedEntities = [])
    {

        //find entities related to the order
        $shop = isset($loadedEntities['shop']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Shop::class, $orderStruct->getLanguageShopId());
        $paymentMethod = isset($loadedEntities['paymentMethod']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
        $customer = isset($loadedEntities['customer']) ? $loadedEntities['customer'] : $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());
        $billingAddress = isset($loadedEntities['billingAddress']) ? $loadedEntities['billingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getBillingAddressId());
        $shippingAddress = isset($loadedEntities['shippingAddress']) ? $loadedEntities['shippingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getShippingAddressId());

        //TODO move to TaxHelper

        $shippingTaxRate = TaxHelper::getShippingTaxRate($orderStruct);
        $shippingTax = $shippingTaxRate > 0 ? $orderStruct->getShippingCostsTaxRate() : 0;

        //looks like vat is always a whole number, so I'll round --> wrong!! // 2019-05-04 TODO verify
        //TODO move to TaxHelper
        $shippingTax = round($shippingTax, 2);
        $shippingCost = $orderStruct->getNetOrder() ? $orderStruct->getShippingCostsNet() : $orderStruct->getShippingCosts();

        $installmentDetails = null;
        $bankData = null;
        if(PaymentMethods::isInstallment($paymentMethod)) {
            $installmentDetails = $this->sessionHelper->getInstallmentDetails();
            if ($installmentDetails->getPaymentSubtype() == PaymentSubType::PAY_TYPE_DIRECT_DEBIT) {  //is rate payment with elv subtype
                $bankData = $this->sessionHelper->getBankData($billingAddress);
            }
        } else if(PaymentMethods::PAYMENT_DEBIT === $paymentMethod->getName()) { // is elv payment
            $bankData = $this->sessionHelper->getBankData($billingAddress);
        }

        return new PaymentRequestData(
            $paymentMethod,
            $customer,
            $billingAddress,
            $shippingAddress,
            array_merge(['shipping'], $orderStruct->getPositions()),
            $shippingCost,
            $shippingTax,
            $this->dfpService->getDfpId(true),
            $shop,
            $orderStruct->getTotal(),
            $orderStruct->getCurrencyId(),
            //$orderStruct->getNetOrder(),
            $bankData,
            $installmentDetails
        );
    }

    public function createFromFrontendSession() {

        $session = $this->sessionHelper->getSession();
        $customer = $this->sessionHelper->getCustomer();
        $billingAddress = $this->sessionHelper->getBillingAddress($customer);
        $shippingAddress = $this->sessionHelper->getShippingAddress($customer);
        $paymentMethod = $this->sessionHelper->getPaymentMethod($customer);

        $content = Shopware()->Modules()->Basket()->sGetBasketData();


        //get total amount
        $user = $session->sOrderVariables['sUserData'];
        $basket = $session->sOrderVariables['sBasket'];
        if (!empty($user['additional']['charge_vat'])) {
            $totalAmount = empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
        } else {
            $totalAmount = $basket['AmountNetNumeric'];
        }

        return new PaymentRequestData(
            $paymentMethod,
            $customer,
            $billingAddress,
            $shippingAddress,
            array_merge(['shipping'], $content['content']),
            $session->sOrderVariables['sBasket']['sShippingcosts'],
            $session->sOrderVariables['sBasket']['sShippingcostsTax'],
            $this->dfpService->getDfpId(false),
            Shopware()->Shop(),
            $totalAmount,
            $session->sOrderVariables['sBasket']['sCurrencyId'],
            $this->sessionHelper->getBankData($billingAddress),
            $this->sessionHelper->getInstallmentDetails()
        );
    }
}
