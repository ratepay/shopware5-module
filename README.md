# RatePAY GmbH - Shopware Payment Module
============================================

|Module | RatePAY Module for Shopware
|------|----------
|Author | Annegret Seufert
|Shop Version | `5.0.x` `5.1.x` `5.2.x` `5.3.x`
|Version | `5.2.4`
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

### Version 5.2.4 - Released 2018-09-13


### Version 5.2.3 - Released 2018-08-28
* Fix tax on shipping item in backend orders
* Improve compatibility with other (payment) modules
* Extend plugin update function to preserve backend credentials
* Fix problems with partial send/cancel/return operations
* Fix payment request error on versions of Shopware earlier than 5.2.0

### Version 5.2.2 - Released 2018-08-17
* hotfix cronjob install in older shopware versions fails if entry already exists

### Version 5.2.1 - Released 2018-08-16
* hotfix cronjob class not found error

### Version 5.2.0 - Released 2018-08-14
* add ability to create backend orders
* add system-wide support for customer groups with net prices

### Version 5.1.1 - Released 2018-08-08
* hotfix plugin update function

### Version 5.1.0 - Released 2018-08-02
* add compatibility to PHP-7.2
* frontend changes on checkout/payment pages
* add setting to handle shipping-item with older API
* manage custom attributes via CrudService
* add common getter to identify installment-elv

### Version 5.0.7 - Released 2018-07-03
* add complete bidirectionally
* add batch processing
* refactor Bootstrap
* fix alternative address option

### Version 5.0.6 - Released 2018-05-07
* remove special company name field
* add terms and conditions

### Version 5.0.5.1 - Released 2018-04-26
* add downward compatibility for old module

### Version 5.0.5 - Released 2018-01-24
* no partial delivery for installments
* fix b2b product calculation
* remove vatId for b2b customers
* fix order delete function

### Version 5.0.4.1 - Released 2017-11-29
* change company address

### Version 5.0.4 - Released 2017-11-17
* add payment method installment 0 %
* code refactoring
* add optional payment confirm

### Version 5.0.3 - Released 2017-09-25
* add country belgium
* add country netherlands
* add language EN, NL and FR

### Version 5.0.2 - Released 2017-08-31
* fix multishops

### Version 5.0.1 - Released 2017-07-14
* new installment calculator design
* make automatic profile request after module update
* fix bank account autofill problem

### Version 5.0.0 - Released 2017-06-26
* change the requests to ne new ratepay library
* add php 7 compatibility
* add bidirectional function

### Version 4.3.0 - Released 2018-03-01
* add backend panels without overwriting them
* compatibility for Shopware 5.4.0

### Version 4.2.93 - Released 2017-11-29
* change company address

### Version 4.2.92 - Released 2017-07-26
* fix credit/debit bug

### Version 4.2.91 - Released 2017-04-12
* fix for checkout javascript problem

### Version 4.2.9 - Released 2017-04-07
* change installment elv payment information

### Version 4.2.8 - Released 2017-04-07
* fix responsive mobile view

### Version 4.2.7 - Released 2017-03-21
* add payment method installment elv

### Version 4.2.6 - Released 2017-03-10
* add debit backend function
* add functionality to refill the inventory after cancellation/retour
* Fix no debit/credit after retour/cancellation
* Fix no shipping after cancellation
* Fix no cancellation after shipping
* Fix no retour before shipping

### Version 4.2.5 - Released 2017-03-02
* SEPA - BIC field removed
* IBAN country prefix validation removed

### Version 4.2.4 - Released 2016-12-20
* Fixed DOB issue

### Version 4.2.3 - Released 2016-12-20
* Fixed sandbox warning

### Version 4.2.2 - Released 2016-12-14
* Improved payment method activation routine
* Added invoicing block inside CONFIRMATION DELIVER
* Improved customer messages in refusals (Extended Response)
* Few frontend changes
* Changed controller&action check in preValidation to avoid trouble with third party plugin controllers
* Transferred rate calc necessary values to DB and removed CONFIGURATION REQUEST
* Checkout warning in case of sandbox mode

* Implemented additional max limit on b2b orders
* Fixed payment change credit method

### Version 4.2.1 - Released 2016-08-31
* Date of payment is now set automatically (cleareddate)
* Improved detection of divergent shipping address (Shopware >=5.2.0)
* Remove deprecated RatePAY additional order attributes on update

### Version 4.2.0 - Released 2016-08-29
* Mapping of new SWAG address management (Compatibility with Shopware 5.2.x)
* CSRF protection implemented (Compatibility with Shopware 5.2.x)
* Fixed compatibility with SWAG import/export function
* Improved DFP creation
* Compatibility with CH and CHF
* Further minor changes and fixes

### Version 4.1.5 - Released 2016-05-26
* Adjusted frontend controller URL (SSL)
* Conveyed sUniqueID to checkout controller

### Version 4.1.4 - Released 2016-04-26
* Payment information additionally saved in order additional fields

### Version 4.1.3 - Released 2016-04-25
* Compatibility with deactivated conditions checkbox

### Version 4.1.2 - Released 2016-04-13
* Fixed get sandbox bug
* Changed ZGB/DSE link in SEPA text
* Enhanced unistall - deleting of Ratepay order attributes
* Ratepay order attributes now nullable
* Prevented error in case of call by crawler
* DOB not requested anymore in case of b2b
* Improved DFP token creation

### Version 4.1.1 - Released 2015-11-26
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

### Version 4.1.0
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
