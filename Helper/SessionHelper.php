<?php


namespace RpayRatePay\Helper;


use Enlight_Components_Session_Namespace;
use RpayRatePay\DTO\BankData;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SessionHelper
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    public function __construct(ContainerInterface $container)
    {
        if($container->has('shop')) {
            //frontend request
            $this->session = $container->get('session');
        } else if($container->has('backendsession')) {
            $this->session = $container->get('backendsession');
        }
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
     * @param Customer $customer
     * @return BankData|null
     */
    public function getBankData(Address $customerAddressBilling, Customer $customer) {
        $sessionData = $this->getData('bankData_c'.$customer->getId());
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


    protected function setData($key, $value) {
        $this->session->RatePay[$key] = $value;
    }

    protected function getData($key) {
        return $this->session->RatePay[$key];
    }

    public function getInstallmentDetails()
    {
        return null; //TODO implement
    }

}
