<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Factory;


use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Helper\SessionHelper;
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
        $orderStruct = clone $orderStruct;

        //find entities related to the order
        $shop = isset($loadedEntities['shop']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Shop::class, $orderStruct->getLanguageShopId());
        $paymentMethod = isset($loadedEntities['paymentMethod']) ? $loadedEntities['paymentMethod'] : $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
        $customer = isset($loadedEntities['customer']) ? $loadedEntities['customer'] : $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());
        $billingAddress = isset($loadedEntities['billingAddress']) ? $loadedEntities['billingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getBillingAddressId());
        $shippingAddress = isset($loadedEntities['shippingAddress']) ? $loadedEntities['shippingAddress'] : $this->modelManager->find(Address::class, $orderStruct->getShippingAddressId());

        $taxFree = $orderStruct->isTaxFree();
        $items = $orderStruct->getPositions();

        //the following lines will fix the tax rates
        foreach ($items as $i => $item) {
            $item = clone $item; // we clone it, so we can modify a few things without breaking the logic of shopware
            $items[$i] = $item;
            if ($orderStruct->getNetOrder()) {
                $item->setPrice(round($item->getPrice() * (1 + ($item->getTaxRate() / 100)), 2));
            } else if ($taxFree) {
                $item->setTaxRate(0);
            }
        }

        $installmentDetails = null;
        $bankData = null;
        if (PaymentMethods::isInstallment($paymentMethod)) {
            $installmentDetails = $this->sessionHelper->getInstallmentDetails();
            if ($installmentDetails->getPaymentType() === PaymentFirstDay::PAY_TYPE_DIRECT_DEBIT) {
                $bankData = $this->sessionHelper->getBankData($billingAddress);
            }
        } else if (PaymentMethods::PAYMENT_DEBIT === $paymentMethod->getName()) { // is elv payment
            $bankData = $this->sessionHelper->getBankData($billingAddress);
        }

        return new PaymentRequestData(
            $paymentMethod,
            $customer,
            $billingAddress,
            $shippingAddress,
            array_merge(['shipping'], $items),
            $orderStruct->getShippingCosts(),
            $taxFree ? 0 : $orderStruct->getShippingCostsTaxRate(),
            $this->dfpService->getDfpId(true),
            $shop,
            $orderStruct->getTotal(),
            $orderStruct->getCurrencyId(),
            $bankData,
            $installmentDetails
        );
    }

    public function createFromFrontendSession()
    {

        $session = $this->sessionHelper->getSession();
        $customer = $this->sessionHelper->getCustomer();
        $billingAddress = $this->sessionHelper->getBillingAddress($customer);
        $shippingAddress = $this->sessionHelper->getShippingAddress($customer);
        $paymentMethod = $this->sessionHelper->getPaymentMethod($customer);

        $content = Shopware()->Modules()->Basket()->sGetBasketData(); // TODO no static access

        //get total amount
        $user = $session->sOrderVariables['sUserData'];
        $basket = (array)$session->sOrderVariables['sBasket']; //we clone it, so we can modify the array without modify the session
        $basket['content'] = $content['content'];
        unset($content); //prevent later use

        $totalAmount = $this->sessionHelper->getTotalAmount();


        $taxFree = $this->sessionHelper->getSession()->get('taxFree');
        $showNet = $customer->getGroup()->getTax() == false; // false == show prices included tax
        //the following code block fix the taxes
        foreach ($basket['content'] as &$item) {
            if ($taxFree) {
                $item['priceNumeric'] = $item['netprice'];
                $item['tax_rate'] = 0;
            } else if ($showNet) {
                $item['priceNumeric'] = round($item['netprice'] * (1 + ($item['tax_rate'] / 100)), 2);
            } else {
                //$item['priceNumeric'] = $item['priceNumeric']; // no price change
            }
        }
        $shippingCost = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();     //TODO no static access
        if ($taxFree) {
            $shippingCost['tax'] = 0;
            $shippingCost['brutto'] = $shippingCost['netto'];
        }

        return new PaymentRequestData(
            $paymentMethod,
            $customer,
            $billingAddress,
            $shippingAddress,
            array_merge(['shipping'], $basket['content']),
            $shippingCost['brutto'],
            $shippingCost['tax'],
            $this->dfpService->getDfpId(false),
            Shopware()->Shop(),                                         //TODO no static access
            $totalAmount,
            $basket['sCurrencyId'],
            $this->sessionHelper->getBankData($billingAddress),
            $this->sessionHelper->getInstallmentDetails()
        );
    }
}
