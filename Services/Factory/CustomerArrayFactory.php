<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Factory;


use RpayRatePay\Component\Mapper\PaymentRequestData;
use RpayRatePay\Services\Config\ConfigService;
use Shopware\Models\Customer\Address;

class CustomerArrayFactory
{
    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

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
            ],
        ];

        if ($billingAddress->getPhone()) {
            $data['Contacts']['Phone'] = [
                'DirectDial' => $billingAddress->getPhone()
            ];
        } else {
            // RATEPLUG-67
            $data['Contacts']['Phone'] = [
                'AreaCode' => '030',
                'DirectDial' => '33988560'
            ];
        }

        if ($billingAddress->getCompany()) {
            $data['CompanyName'] = $billingAddress->getCompany();
            if(!empty($billingAddress->getVatId())) {
                $data['VatId'] = $billingAddress->getVatId();
            }
        }

        if ($paymentRequestData->getBankData()) {
            $bankDataDTO = $paymentRequestData->getBankData();
            $bankData = [
                'Owner' => $bankDataDTO->getAccountHolder()
            ];
            if ($bankDataDTO->getBankCode() !== null) {
                $bankData['BankAccountNumber'] = $bankDataDTO->getAccountNumber();
                $bankData['BankCode'] = $bankDataDTO->getBankCode();
            } else {
                $bankData['Iban'] = $bankDataDTO->getIban();
            }

            $data['BankAccount'] = $bankData;
        }

        return $data;
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

    private function _getCheckoutAddress(Address $address, $addressType)
    {
        $return = [
            'Type' => strtolower($addressType),
            'Street' => $address->getStreet(),
            'ZipCode' => $address->getZipCode(),
            'City' => $address->getCity(),
            'CountryCode' => $address->getCountry()->getIso(),
        ];

        if ($address->getAdditionalAddressLine1() || $address->getAdditionalAddressLine2()) {
            switch ($this->configService->getAdditionalAddressLineSetting()) {
                case 'concat':
                    $additional = $address->getAdditionalAddressLine1() . ' ' . $address->getAdditionalAddressLine2();
                    break;
                case 'line1':
                    $additional = $address->getAdditionalAddressLine1();
                    break;
                case 'line2':
                    $additional = $address->getAdditionalAddressLine2();
                    break;
                default:
                    $additional = '';
            }
            $additional = trim($additional);
            if (!empty($additional)) {
                $return['StreetAdditional'] = $additional;
            }
        }

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

}
