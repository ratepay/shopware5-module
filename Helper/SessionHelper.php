<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Helper;


use Doctrine\ORM\EntityManager;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Front;
use Exception;
use RpayRatePay\Bootstrap\PaymentMeans;
use RpayRatePay\DTO\BankData;
use RpayRatePay\DTO\InstallmentDetails;
use RpayRatePay\DTO\InstallmentRequest;
use RpayRatePay\Enum\PaymentFirstDay;
use RpayRatePay\Util\BankDataUtil;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SessionHelper
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected $isFrontendSession;

    /** @var Customer */
    private $loadedCustomer;
    /** @var Address */
    private $loadedBillingAddress;
    /** @var Address */
    private $loadedShippingAddress;
    /** @var PaymentMeans */
    private $loadedPaymentMethod;

    public function __construct(
        ModelManager $entityManager,
        ContainerInterface $container,
        Enlight_Controller_Front $front
    )
    {
        $this->entityManager = $entityManager;
        switch ($front->Request()->getParam('module')) {
            case 'frontend':
                $this->session = $container->get('session');
                $this->isFrontendSession = true;
                break;
            case 'backend':
                $this->session = $container->get('backendsession');
                $this->isFrontendSession = false;
                break;
        }
    }

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

    public function setData($key = null, $value = null)
    {
        if ($key === null) {
            $this->session->offsetUnset('RatePay');
        } else {
            if ($value !== null) {
                $this->session->RatePay[$key] = $value;
            } else {
                unset($this->session->RatePay[$key]);
            }
        }
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

    public function getData($key, $default = null)
    {
        return isset($this->session->RatePay[$key]) ? $this->session->RatePay[$key] : $default;
    }

    /**
     * @return InstallmentDetails
     */
    public function getInstallmentDetails()
    {
        $data = $this->getData('ratenrechner');

        $object = null;
        if (is_array($data) && isset($data['rate'])) {
            $object = new InstallmentDetails();
            $object->setTotalAmount($data['total_amount']);
            $object->setAmount($data['amount']);
            $object->setInterestRate($data['interest_rate']);
            $object->setInterestAmount($data['interest_amount']);
            $object->setServiceCharge($data['service_charge']);
            $object->setAnnualPercentageRate($data['annual_percentage_rate']);
            $object->setMonthlyDebitInterest($data['monthly_debit_interest']);
            $object->setNumberOfRatesFull($data['number_of_rates']);
            $object->setRate($data['rate']);
            $object->setLastRate($data['last_rate']);
            $object->setPaymentType($data['payment_subtype']);
        }
        return $object;
    }

    public function setInstallmentDetails($totalAmount, $amount, $interestRate, $interestAmount, $serviceCharge, $annualPercentageRate, $monthlyDebitInterest, $numberOfRatesFull, $rate, $lastRate, $paymentType, InstallmentRequest $installmentRequest)
    {
        $this->setData('ratenrechner', [
            'total_amount' => $totalAmount,
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'interest_amount' => $interestAmount,
            'service_charge' => $serviceCharge,
            'annual_percentage_rate' => $annualPercentageRate,
            'monthly_debit_interest' => $monthlyDebitInterest,
            'number_of_rates' => $numberOfRatesFull,
            'rate' => $rate,
            'last_rate' => $lastRate,
        ]);
        $this->setInstallmentPaymentType($paymentType);
        $this->setData('installment_calculator_input', $installmentRequest->toArray());
    }

    public function setInstallmentPaymentType($paymentType)
    {
        $data = $this->getData('ratenrechner');
        $data['payment_subtype'] = $paymentType;
        $data['payment_firstday'] = PaymentFirstDay::getFirstDayForPayType($paymentType);
        $data['dueDate'] = $data['payment_firstday'];
        $this->setData('ratenrechner', $data);
    }

    public function unsetInstallmentDetails()
    {
        $this->setData('ratenrechner', null);
        $this->setData('installment_calculator_input', null);
    }

    public function getInstallmentRequestDTO()
    {
        $data = $this->getData('installment_calculator_input') ?: [];
        $dto = new InstallmentRequest();
        $dto->fromArray($data);
        return $dto;
    }

    public function cleanUp()
    {
        $this->setData(null, null);
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

    public function getSession()
    {
        return $this->session;
    }

    /**
     * this functions add a value to a array in the session.
     * if the key does not exist in the session, the function will create a new array.
     * if the key already exist in the session and the value is not a array, the existing value will added to a new array.
     * @param $key
     * @param $value
     */
    public function addData($key, $value)
    {
        $data = $this->getData($key, []);
        if (is_array($data) == false) {
            $data = [$data];
        }
        $data[] = $value;
        $this->setData($key, $data);
    }
}
