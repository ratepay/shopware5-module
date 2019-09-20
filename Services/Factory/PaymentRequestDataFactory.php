<?php


namespace RpayRatePay\Services\Factory;


use RatePAY\Service\Math;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Services\DfpService;
use Shopware\Components\Model\ModelManager;
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

    public function __construct(
        ModelManager $modelManager,
        DfpService $dfpService
    )
    {
        $this->modelManager = $modelManager;
        $this->dfpService = $dfpService;
    }

    public function createFromOrderStruct(OrderStruct $orderStruct, array $loadedEntities = []) {

        //find entities related to the order
        $shop = isset($loadedEntities['shop']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Shop::class, $orderStruct->getLanguageShopId());
        $paymentMethod = isset($loadedEntities['paymentMethod']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
        $customer = isset($loadedEntities['customer']) ? $loadedEntities['customer'] : $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());
        $billing = isset($loadedEntities['billingAddress']) ? $loadedEntities['billingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getBillingAddressId());
        $shipping = isset($loadedEntities['shippingAddress']) ? $loadedEntities['shippingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getShippingAddressId());

        $shippingTaxRate = TaxHelper::taxFromPrices($orderStruct->getShippingCostsNet(), $orderStruct->getShippingCosts());
        $shippingTax = $shippingTaxRate > 0 ? $orderStruct->getShippingCostsTaxRate() : 0;

        //looks like vat is always a whole number, so I'll round --> wrong!! // 2019-05-04 TODO verify
        $shippingTax = round($shippingTax, 2);

        $shippingCost = $orderStruct->getNetOrder() ? $orderStruct->getShippingCostsNet() : $orderStruct->getShippingCosts();

        return new PaymentRequestData(
            $paymentMethod,
            $customer,
            $billing,
            $shipping,
            array_merge(['shipping'], $orderStruct->getPositions()),
            $shippingCost,
            $shippingTax,
            $this->dfpService->getDfpId(true),
            $shop,
            $orderStruct->getTotal(),
            $orderStruct->getCurrencyId(),
            $orderStruct->getNetOrder()
        );
    }
}
