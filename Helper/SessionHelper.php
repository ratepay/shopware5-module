<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Helper;


use Exception;
use RpayRatePay\Bootstrap\PaymentMeans;
use RpayRatePay\Component\Service\ValidationLib;
use RpayRatePay\DTO\BankData;
use RpayRatePay\DTO\PaymentConfigSearch;
use RpayRatePay\Util\BankDataUtil;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;

class SessionHelper extends AbstractSessionHelper
{

    /** @var Customer */
    private $loadedCustomer;
    /** @var Address */
    private $loadedBillingAddress;
    /** @var Address */
    private $loadedShippingAddress;
    /** @var PaymentMeans */
    private $loadedPaymentMethod;

    public function getBillingAddress(Customer $customer = null)
    {
        if ($this->isFrontendSession === false) {
            throw new Exception('not implemented');
        }
        if ($this->loadedBillingAddress) {
            return $this->loadedBillingAddress;
        }

        $addressId = $this->session['checkoutBillingAddressId'];
        if ($addressId > 0) {
            $this->loadedBillingAddress = $this->entityManager->find(Address::class, $addressId);
        } else if ($customer) {
            $this->loadedBillingAddress = $customer->getDefaultBillingAddress();
        } else {
            $customer = $this->getCustomer();
            if ($customer !== null) {
                $this->loadedBillingAddress = $customer->getDefaultBillingAddress();
            }
        }
        return $this->loadedBillingAddress;
    }

    public function getCustomer()
    {
        if ($this->isFrontendSession === false) {
            throw new Exception('not implemented');
        }

        if ($this->loadedCustomer) {
            return $this->loadedCustomer;
        }

        $customerId = $this->session->get('sUserId');
        if (empty($customerId)) {
            return null;
        }

        return $this->loadedCustomer = $this->entityManager->find(Customer::class, $customerId);
    }

    public function getShippingAddress(Customer $customer = null)
    {
        if ($this->isFrontendSession === false) {
            throw new Exception('not implemented');
        }

        if ($this->loadedShippingAddress) {
            return $this->loadedShippingAddress;
        }

        $addressId = $this->session['checkoutShippingAddressId'];
        if ($addressId > 0) {
            $this->loadedShippingAddress = $this->entityManager->find(Address::class, $addressId);
        } else if ($customer) {
            $this->loadedShippingAddress = $customer->getDefaultShippingAddress();
        } else {
            $customer = $this->getCustomer();
            if ($customer !== null) {
                $this->loadedShippingAddress = $customer->getDefaultBillingAddress();
            }
        }
        return $this->loadedShippingAddress;
    }

    public function getPaymentMethod(Customer $customer = null)
    {
        if ($this->isFrontendSession === false) {
            throw new Exception('not implemented');
        }

        if ($this->loadedPaymentMethod) {
            return $this->loadedPaymentMethod;
        }
        $customer = $customer ?: $this->getCustomer();

        $sessionVars = $this->session->get('sOrderVariables');
        $paymentId = isset($sessionVars['sPayment']['id']) ? $sessionVars['sPayment']['id'] : $customer->getPaymentId();
        return $this->loadedPaymentMethod = $this->entityManager->find(Payment::class, $paymentId);
    }

    public function removeBankData($customerId)
    {
        $this->setData('bankData_c' . $customerId, null);
    }

    public function setBankData($customerId, $accountHolder = null, $accountNumber = null)
    {
        if ($accountNumber !== null) {
            $this->setData('bankData_c' . $customerId, [
                'customerId' => $customerId,
                'accountHolder' => $accountHolder,
                'account' => $accountNumber
            ]);
        } else {
            $this->setData('bankData_c' . $customerId, null);
        }
    }

    /**
     * @param Address $customerAddressBilling
     * @return BankData|null
     */
    public function getBankData(Address $customerAddressBilling)
    {
        $sessionData = $this->getData('bankData_c' . $customerAddressBilling->getCustomer()->getId());
        if ($sessionData === null) {
            return null;
        }

        $accountHolder = $sessionData['accountHolder'];
        $iban = $sessionData['account'];

        $accountHolder = $accountHolder ?: BankDataUtil::getDefaultAccountHolder($customerAddressBilling);
        return new BankData($accountHolder, $iban);
    }


    /**
     * be careful if you use this function!
     * Shopware has a bug, that the sOrderVariables got not cleaned after the order was successful
     * the next problem is, that if the customer got to the payment/shipping page, this array (and its values) may not set.
     * @return mixed
     * @throws Exception
     */
    public function getTotalAmount()
    {
        if ($this->isFrontendSession === false) {
            throw new Exception('not implemented');
        }

        $user = $this->session->sOrderVariables['sUserData'];
        $basket = (array)$this->session->sOrderVariables['sBasket'];
        if (!empty($user['additional']['charge_vat'])) {
            return empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
        } else {
            return $basket['AmountNetNumeric'];
        }
    }

    /**
     * @param string|int|Payment $paymentMethod
     */
    public function getPaymentConfigSearchObject($paymentMethod)
    {
        $billingAddress = $this->getBillingAddress();
        $shippingAddress = $this->getShippingAddress() ?: $billingAddress;

        return $this->getPaymentConfigSearchObjectWithoutCustomerData($paymentMethod)
            ->setBillingCountry($billingAddress->getCountry()->getIso())
            ->setShippingCountry($shippingAddress->getCountry()->getIso())
            ->setNeedsAllowDifferentAddress(ValidationLib::areBillingAndShippingSame($billingAddress, $shippingAddress) === false)
            ->setIsB2b(ValidationLib::isCompanySet($billingAddress))
            ->setTotalAmount(floatval(Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']));
    }

    /**
     * @param string|int|Payment $paymentMethod
     */
    public function getPaymentConfigSearchObjectWithoutCustomerData($paymentMethod)
    {
        return (new PaymentConfigSearch())
            ->setPaymentMethod($paymentMethod)
            ->setBackend(false)
            ->setShop(Shopware()->Shop())
            ->setCurrency(Shopware()->Config()->get('currency'));
    }

}
