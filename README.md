# RatePAY GmbH - Shopware Payment Module
============================================

|Module | RatePAY Module for Shopware
|------|----------
|Author | Aarne Welschlau, Annegret Seufert
|Shop Version | `5.0.x` `5.1.x` `5.2.x`
|Version | `5.0.1`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Installation | see below

## Installation
1. Erzeugen Sie das Verzeichnis `RpayRatePay` in `engine/Shopware/Plugins/Community/Frontend`
2. Integrieren Sie den Inhalt in `engine/Shopware/Plugins/Community/Frontend/RpayRatePay`
3. Loggen Sie sich in ihr Shopware-Backend ein
4. Installieren & konfigurieren Sie das Modul

## Install
1. Create Directory `RpayRatePay` in `engine/Shopware/Plugins/Community/Frontend`
2. Merge the content into `engine/Shopware/Plugins/Community/Frontend/RpayRatePay`
3. Log into your Shopware-backend
4. Install & configure the module

## Changelog

### Version Version 5.0.1 - Released 2017-07-14
* new installment calculator design
* make automatic profile request after module update
* fix bank account autofill problem

### Version Version 5.0.0 - Released 2017-06-26
* change the requests to ne new ratepay library
* add php 7 compatibility
* add bidirectional function

### Version Version 4.2.91 - Released 2017-04-12
* fix for checkout javascript problem

### Version Version 4.2.9 - Released 2017-04-07
* change installment elv payment information

### Version Version 4.2.8 - Released 2017-04-07
* fix responsive mobile view

### Version Version 4.2.7 - Released 2017-03-21
* add payment method installment elv

### Version Version 4.2.6 - Released 2017-03-10
* add debit backend function
* add functionality to refill the inventory after cancellation/retour
* Fix no debit/credit after retour/cancellation
* Fix no shipping after cancellation
* Fix no cancellation after shipping
* Fix no retour before shipping

### Version Version 4.2.5 - Released 2017-03-02
* SEPA - BIC field removed
* IBAN country prefix validation removed

### Version Version 4.2.4 - Released 2016-12-20
* Fixed DOB issue

### Version Version 4.2.3 - Released 2016-12-20
* Fixed sandbox warning

### Version Version 4.2.2 - Released 2016-12-14
* Improved payment method activation routine
* Added invoicing block inside CONFIRMATION DELIVER
* Improved customer messages in refusals (Extended Response)
* Few frontend changes
* Changed controller&action check in preValidation to avoid trouble with third party plugin controllers
* Transferred rate calc necessary values to DB and removed CONFIGURATION REQUEST
* Checkout warning in case of sandbox mode

* Implemented additional max limit on b2b orders
* Fixed payment change credit method

### Version Version 4.2.1 - Released 2016-08-31
* Date of payment is now set automatically (cleareddate)
* Improved detection of divergent shipping address (Shopware >=5.2.0)
* Remove deprecated RatePAY additional order attributes on update

### Version Version 4.2.0 - Released 2016-08-29
* Mapping of new SWAG address management (Compatibility with Shopware 5.2.x)
* CSRF protection implemented (Compatibility with Shopware 5.2.x)
* Fixed compatibility with SWAG import/export function
* Improved DFP creation
* Compatibility with CH and CHF
* Further minor changes and fixes

### Version Version 4.1.5 - Released 2016-05-26
* Adjusted frontend controller URL (SSL)
* Conveyed sUniqueID to checkout controller

### Version Version 4.1.4 - Released 2016-04-26
* Payment information additionally saved in order additional fields

### Version Version 4.1.3 - Released 2016-04-25
* Compatibility with deactivated conditions checkbox

### Version Version 4.1.2 - Released 2016-04-13
* Fixed get sandbox bug
* Changed ZGB/DSE link in SEPA text
* Enhanced unistall - deleting of Ratepay order attributes
* Ratepay order attributes now nullable
* Prevented error in case of call by crawler
* DOB not requested anymore in case of b2b
* Improved DFP token creation

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
