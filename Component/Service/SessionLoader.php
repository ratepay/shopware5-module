<?php

namespace  RpayRatePay\Component\Service;

use RatePAY\Service\Math;
use RpayRatePay\Component\Mapper\BankData;
use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Services\DfpService;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;

class SessionLoader
{
    private $session;

    /** @var DfpService  */
    protected $dfpService;

    /**
     * Session constructor with Shopware params Session or BackendSession.
     * @param object $session
     */
    public function __construct($session)
    {
        $this->session = $session;
        $this->dfpService = Shopware()->Container()->get(DfpService::class);
    }

    /**
     * @param $customerId
     * @param $accountNumber
     * @param null $bankCode
     */
    public function setBankData($customerId, $accountNumber, $bankCode = null)
    {
        $this->session->RatePAY['bankdata']['customerId'] = $customerId;
        $this->session->RatePAY['bankdata']['account'] = $accountNumber;
        $this->session->RatePAY['bankdata']['bankcode'] = $bankCode;
    }

    /**
     * @param $customerAddressBilling
     * @param $customerId
     * @return BankData
     * @throws \Exception
     */
    public function getBankData($customerAddressBilling, $customerId)
    {
        $sessionArray = $this->session->RatePAY['bankdata'];

        $customerIdSession = $sessionArray['customerId'];

        //todo, get rid of cast if possible
        if ((int)$customerIdSession !== (int)$customerId) {
            throw new \Exception('Attempt to load bank data for wrong customer! Session Value ' .
                $customerIdSession . ' checked value ' . $customerId . '.');
        }

        $bankCode = $sessionArray['bankcode'];
        $account = $sessionArray['account'];

        $accountHolder = $customerAddressBilling->getFirstname() . ' ' . $customerAddressBilling->getLastname();
        if (!empty($bankCode)) {
            return new BankData($accountHolder, null, $bankCode, $account);
        } else {
            return new BankData($accountHolder, $account);
        }
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     * @param \Shopware\Models\Customer\Address $shipping
     * @return mixed
     */
    private function findAddressShipping($customer, $shippingAddress = null)
    {
        //$customerWrapped = new ShopwareCustomerWrapper($customer, Shopware()->Models());
        if (isset($this->session->RatePAY['checkoutShippingAddressId']) && $this->session->RatePAY['checkoutShippingAddressId'] > 0) {
            $addressModel = Shopware()->Models()->getRepository(Address::class);
            $checkoutAddressShipping = $addressModel->findOneBy(['id' => $this->session->RatePAY['checkoutShippingAddressId'] ? $this->session->RatePAY['checkoutShippingAddressId'] : $this->session->RatePAY['checkoutBillingAddressId']]);
        } else {
            $checkoutAddressShipping = $shippingAddress ? $shippingAddress : $customer->getDefaultShippingAddress();
        }
        return $checkoutAddressShipping;
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     * @return mixed
     */
    private function findAddressBilling($customer)
    {
        if (isset($this->session->RatePAY['checkoutBillingAddressId']) && $this->session->RatePAY['checkoutBillingAddressId'] > 0) {
            $addressModel = Shopware()->Models()->getRepository(Address::class);
            $checkoutAddressBilling = $addressModel->findOneBy(['id' => $this->session->RatePAY['checkoutBillingAddressId']]);
        } else {
            $checkoutAddressBilling = $customer->getDefaultBillingAddress();
        }

        return $checkoutAddressBilling;
    }

    private function findAmountInSession()
    {
        $user = $this->session->sOrderVariables['sUserData'];
        $basket = $this->session->sOrderVariables['sBasket'];
        if (!empty($user['additional']['charge_vat'])) {
            return empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
        } else {
            return $basket['AmountNetNumeric'];
        }
    }

    public function getPaymentRequestData()
    {
        $customer = Shopware()->Models()->find(Customer::class, $this->session->sUserId);

        $items = $this->session->sOrderVariables['sBasket']['content'];

        $shippingCost = $this->session->sOrderVariables['sBasket']['sShippingcosts'];

        $shippingTax = $this->session->sOrderVariables['sBasket']['sShippingcostsTax'];


        $userData = $this->session->sOrderVariables['sUserData'];

        if ($userData['additional']['charge_vat'] == false) {
            // the customergroup must not pay tax
            $shippingTax = 0;
            foreach($items as $i => $item) {
                $items[$i]['tax_rate'] = 0;
            }
        }

        foreach ($items as &$item) {
            if ($item['modus'] == 2) { //is voucher
                if (TaxHelper::taxFromPrices($item['amountnetNumeric'], $item['amountWithTax']) == 0) {
                //if ($item['taxID'] == null) { // does not work cause the tax ID is always null in this case.
                    $item['tax_rate'] = 0;
                }
            }
        }

        $shopContext = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext(); // TODO This will crash in the admin
        $shop = $shopContext->getShop();

        $amount = $this->findAmountInSession();

        return new PaymentRequestData(
            $this->session->sOrderVariables['sUserData']['additional']['payment']['name'],
            $customer,
            $this->findAddressBilling($customer),
            $this->findAddressShipping($customer, null), //TODO pass selected shipping address
            $items,
            $shippingCost,
            $shippingTax,
            $this->dfpService->getDfpId(),
            $shop,
            $amount,
            $this->session->sOrderVariables['sBasket']['sCurrencyId']
        );
    }

    public function setInstallmentData($total_amount, $amount, $interest_rate, $interest_amount, $service_charge, $annual_percentage_rate, $monthly_debit_interest, $number_of_rates, $rate, $last_rate, $payment_firstday)
    {
        /* Saving Data as example in the Session */
        $this->session->RatePAY['ratenrechner']['total_amount'] = $total_amount;
        $this->session->RatePAY['ratenrechner']['amount'] = $amount;
        $this->session->RatePAY['ratenrechner']['interest_rate'] = $interest_rate;
        $this->session->RatePAY['ratenrechner']['interest_amount'] = $interest_amount;
        $this->session->RatePAY['ratenrechner']['service_charge'] = $service_charge;
        $this->session->RatePAY['ratenrechner']['annual_percentage_rate'] = $annual_percentage_rate;
        $this->session->RatePAY['ratenrechner']['monthly_debit_interest'] = $monthly_debit_interest;
        $this->session->RatePAY['ratenrechner']['number_of_rates'] = $number_of_rates;
        $this->session->RatePAY['ratenrechner']['rate'] = $rate;
        $this->session->RatePAY['ratenrechner']['last_rate'] = $last_rate;
        $this->setInstallmentPaymentSubtype($payment_firstday);
    }

    public function setInstallmentPaymentSubtype($subtype)
    {
        $this->session->RatePAY['ratenrechner']['payment_firstday'] = $subtype;
        $this->session->RatePAY['dueDate'] = $subtype;
    }
}
