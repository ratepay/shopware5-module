<?php


namespace RpayRatePay\Helper;


use Doctrine\ORM\EntityManager;
use Enlight_Components_Session_Namespace;
use RpayRatePay\DTO\BankData;
use RpayRatePay\DTO\InstallmentDetails;
use RpayRatePay\Enum\PaymentSubType;
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

    public function __construct(ModelManager $entityManager, ContainerInterface $container)
    {
        $this->entityManager = $entityManager;
        if($container->has('shop')) {
            //frontend request
            $this->session = $container->get('session');
            $this->isFrontendSession = true;
        } else if($container->has('backendsession')) {
            //admin request
            $this->session = $container->get('backendsession');
            $this->isFrontendSession = false;
        }
    }

    public function getBillingAddress(Customer $customer = null) {
        if($this->isFrontendSession === false) {
            throw new \Exception('not implemented');
        }

        $addressId = $this->session['checkoutBillingAddressId'];
        if ($addressId > 0) {
            return $this->entityManager->find(Address::class, $addressId);
        } else if($customer) {
            return $customer->getDefaultBillingAddress();
        } else {
            return $this->getCustomer()->getDefaultBillingAddress();
        }
        return null;
    }
    public function getShippingAddress(Customer $customer = null) {
        if($this->isFrontendSession === false) {
            throw new \Exception('not implemented');
        }

        $addressId = $this->session['checkoutShippingAddressId'];
        if ($addressId > 0) {
            return $this->entityManager->find(Address::class, $addressId);
        } else if($customer) {
            return $customer->getDefaultShippingAddress();
        } else {
            return $this->getCustomer()->getDefaultBillingAddress();
        }
        return null;
    }

    public function getCustomer(){
        if($this->isFrontendSession === false) {
            throw new \Exception('not implemented');
        }

        $customerId = $this->session->get('sUserId');
        if (empty($customerId)) {
            return null;
        }

        return $this->entityManager->find(Customer::class, $customerId);
    }

    public function getPaymentMethod(Customer $customer) {
        if($this->isFrontendSession === false) {
            throw new \Exception('not implemented');
        }
        $sessionVars = $this->session->get('sOrderVariables');
        $paymentId = isset($sessionVars['sPayment']['id']) ? $sessionVars['sPayment']['id'] : $customer->getPaymentId();
        return $this->entityManager->find(Payment::class, $paymentId);
    }

    public function setBankData($customerId, $accountNumber, $bankCode = null)
    {
        $this->setData('bankData_c'.$customerId, [
            'customerId' => $customerId,
            'account' => $accountNumber,
            'bankcode' => $bankCode
        ]);
    }

    /**
     * @param Address $customerAddressBilling
     * @return BankData|null
     */
    public function getBankData(Address $customerAddressBilling) {
        $sessionData = $this->getData('bankData_c'.$customerAddressBilling->getCustomer()->getId());
        if($sessionData == null) {
            return null;
        }

        $bankCode = $sessionData['bankcode'];
        $account = $sessionData['account'];

        $accountHolder = $customerAddressBilling->getFirstname() . ' ' . $customerAddressBilling->getLastname();
        if (!empty($bankCode)) {
            return new BankData($accountHolder, null, $bankCode, $account);
        } else {
            return new BankData($accountHolder, $account);
        }
    }

    protected function setData($key = null, $value = null) {
        $this->session->RatePay = null;
        $this->session->RatePay[$key] = $value;
    }

    protected function getData($key) {
        return $this->session->RatePay[$key];
    }

    /**
     * @return InstallmentDetails
     */
    public function getInstallmentDetails()
    {
        $data = $this->getData('ratenrechner');

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
        $object->setPaymentFirstday($data['payment_firstday']);
        $object->setPaymentSubtype($data['payment_subtype']);
        $object->setDueDate($data['dueDate']);
        return $object;
    }

    public function setInstallmentData($totalAmount, $amount, $interestRate, $interestAmount, $serviceCharge, $annualPercentageRate, $monthlyDebitInterest, $numberOfRatesFull, $rate, $lastRate, $paymentSubtype)
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
        $this->setInstallmentPaymentSubtype($paymentSubtype); //todo this is the paymentFirstDay
    }

    public function setInstallmentPaymentSubtype($paymentFirstDay)
    {
        $data = $this->getData('ratenrechner');
        $data['payment_subtype'] = PaymentSubType::getPayTypByFirstPayDay($paymentFirstDay); //TODO documentation.
        $data['payment_firstday'] = $paymentFirstDay;
        $data['dueDate'] = $paymentFirstDay;
        $this->setData('ratenrechner', $data);
    }

    public function getSession()
    {
        return $this->session;
    }

    public function cleanUp() {
        $this->setData(null, null);
    }
}
