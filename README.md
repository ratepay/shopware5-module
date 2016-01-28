# RatePAY GmbH - Shopware Payment Module
============================================

|Module | RatePAY Module for Shopware
|------|----------
|Author | Aarne Welschlau
|Shop Version | `5.0.x` `5.1.x`
|Version | `4.1.2`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Installation | see below

## Installation
1. Erzeugen Sie das Verzeichnis `RpayRatePay` in `engine/Shopware/Plugins/Default/Frontend`
2. Integrieren Sie den Inhalt in `engine/Shopware/Plugins/Default/Frontend/RpayRatePay`
3. Loggen Sie sich in ihr Shopware-Backend ein
4. Installieren & konfigurieren Sie das Modul

## Install
1. Create Directory `RpayRatePay` in `engine/Shopware/Plugins/Default/Frontend`
2. Merge the content into `engine/Shopware/Plugins/Default/Frontend/RpayRatePay`
3. Log into your Shopware-backend
4. Install & configure the module

## Changelog

### Version Version 4.1.2 - Released 2016-01-29
* Fixed get sandbox bug
* Changed ZGB/DSE link in SEPA text
* Enhanced unistall - deleting of Ratepay order attributes
* Ratepay order attributes now nullable

### Version Version 4.1.1 - Released 2015-11-26
* Fixed DFP
* Fixed DFP DB update bug
* Fixed JS checkout form validation
* Fixed JS not defined resources
* Fixed JS checkout button hook
* Fixed RR curl bug
* New DB update procedure
* No hiding of payment methods in sandbox mode
* Account holder is always customer name by billing address
* Redesign of rejection page

### Version Version 4.1.0
* fixed compatibility for 5.0.4 - 5.1.1
* added device-fingerprinting
* fixed frontend validation
* fixed DB allows NULL
* fixed backend tab "Artikelverwaltung"

### Version 4.0.3
* Refactored module logic

### Version 4.0.2
* fixed bug for adjusting correct payment status and oder status
* fixed bug in address validation

### Version 4.0.1
* fixed SSL & CURL bug
* fixed problems with combined field (street+number)

### Version 4.0.0
* Shopware 5 module (without responsive design support)
