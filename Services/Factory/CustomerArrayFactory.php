<?php


namespace RpayRatePay\Services\Factory;


use RpayRatePay\Component\Mapper\PaymentRequestData;
use Shopware\Models\Customer\Address;

class CustomerArrayFactory
{

    const ARRAY_KEY = 'Customer';

    public function getData(PaymentRequestData $paymentRequestData)
    {

        $customer = $paymentRequestData->getCustomer();
        $billingAddress = $paymentRequestData->getBillingAddress();
        $shippingAddress = $paymentRequestData->getShippingAddress();

        $gender = 'u';
        $salutation = $billingAddress->getSalutation();
        if ($salutation === 'ms') {
            $salutation = 'Frau';
            $gender = 'f';
        } elseif ($salutation === 'mr') {
            $salutation = 'Herr';
            $gender = 'm';
        }

        $data = [
            'Gender' => $gender,
            'Salutation' => $salutation,
            'FirstName' => $billingAddress->getFirstName(),
            'LastName' => $billingAddress->getLastName(),
            'Language' => strtolower($paymentRequestData->getLang()),
            'DateOfBirth' => $billingAddress->getCompany() ? null : $paymentRequestData->getBirthday(),
            'IpAddress' => $this->_getCustomerIP(),
            'Addresses' => [
                [
                    'Address' => $this->_getCheckoutAddress($billingAddress, 'BILLING')
                ], [
                    'Address' => $this->_getCheckoutAddress($shippingAddress, 'DELIVERY')
                ]
            ],
            'Contacts' => [
                'Email' => $customer->getEmail(),
                'Phone' => [
                    'DirectDial' => $billingAddress->getPhone()
                ],
            ],
        ];

        if ($billingAddress->getCompany()) {
            $data['CompanyName'] = $billingAddress->getCompany();
            $data['VatId'] = $billingAddress->getVatId();
        }

        if (count($paymentRequestData->getBankData())) {
            $data['BankAccount'] = $paymentRequestData->getBankData(); //TODO verify if data is correct?
        }

        return $data;
    }

    private function _getCheckoutAddress(Address $address, $addressType)
    {
        $return = [
            'Type' => strtolower($addressType),
            'Street' => $address->getStreet(),
            'ZipCode' => $address->getZipCode(),
            'City' => $address->getCity(),
            'CountryCode' => $address->getCountry()->getIso(),
        ];

        if ($addressType === 'DELIVERY') {
            $return['FirstName'] = $address->getFirstName();
            $return['LastName'] = $address->getLastName();
        }

        $company = $address->getCompany();
        if (!empty($company)) {
            $return['Company'] = $address->getCompany();
        }

        return $return;
    }

    /**
     * Returns the IP Address for the current customer
     *
     * @return string
     */
    private function _getCustomerIP()
    {
        //TODO refactor function
        $customerIp = null;
        if (!is_null(Shopware()->Front())) {
            $customerIp = Shopware()->Front()->Request()->getClientIp();
        } else {
            if (!empty($this->_transactionId)) {
                $customerIp = Shopware()->Db()->fetchOne(
                    'SELECT `remote_addr` FROM `s_order` WHERE `transactionID`=' . $this->_transactionId
                );
            } else {
                $customerIp = $_SERVER['SERVER_ADDR'];
            }
        }

        return $customerIp;
    }

}
