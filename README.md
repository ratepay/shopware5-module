# RatePAY GmbH - Shopware Payment Module
============================================

|Module | RatePAY Module for Shopware
|------|----------
|Author | Aarne Welschlau
|Shop Version | `5.0.x` `5.1.x`
|Version | `4.1.1`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Installation | see below

## Installation
1. Laden Sie sich das Modul hier herunter(https://github.com/ratepay/shopware5-module/archive/4.1.1.zip)
2. Entpacken Sie die Zipdatei.
3. Integrieren Sie den Inhalt des "shopware5-module-4.1.1"-Ordners in `engine/Shopware/Plugins/Default/Frontend/RpayRatePay`
4. Loggen Sie sich in ihr Shopware-Backend ein
5. Installieren & konfigurieren Sie das Modul

## Install
1. Download the module here ([https://github.com/ratepay/shopware5-module/archive/4.1.1.zip])
2. Extract the zipfile.
3. Create Directory `RpayRatePay` in `engine/Shopware/Plugins/Default/Frontend`
4. Merge the content of the "shopware-module-4.1.1"-folder into `engine/Shopware/Plugins/Default/Frontend/RpayRatePay`
5. Log into your Shopware-backend
6. Install & configure the module

## Changelog

### Version Version 4.1.1 - Released 2015-11-12
* Fixed DFP update bug
* No hiding of payment methods in sandbox mode
* Account holder is always customer name by billing address

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