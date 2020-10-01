# Ratepay GmbH - Shopware Payment Module
============================================

|Module | Ratepay Module for Shopware
|------|----------
|Shop Version | `5.5.x` - `5.5.x`
|Version | `5.4.1`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Full Documentation | https://ratepay.gitbook.io/shopware/

## Installation
1. Erzeugen Sie das Verzeichnis `RpayRatePay` in `custom/plugins/`
2. Integrieren Sie den Inhalt in `custom/plugins/RpayRatePay`
3. gegebenenfalls composer install ausführen in dem Verzeichnis `custom/plugins/RpayRatePay`
4. Loggen Sie sich in ihr Shopware-Backend ein
5. Installieren & konfigurieren Sie das Modul

## Install
1. Create Directory `RpayRatePay` in `custom/plugins/`
2. Merge the content into  in the folder `custom/plugins/RpayRatePay`
3. execute the command composer install in the folder `custom/plugins/RpayRatePay`
4. Log into your Shopware-backend
5. Install & configure the module

## Changelog
Siehe plugin.xml


## Request services
There are a three request services registered:
- `\RpayRatePay\Services\Request\PaymentDeliveryService`
    
    Use this service to do deliveries for order.
- `RpayRatePay\Services\Request\PaymentReturnService`

    Use this service to do returns for order.
- `RpayRatePay\Services\Request\PaymentCancelService`

    Use this service to do cancellations for order.

### Usage of the request services

Get the request service via dependency injection. The id of the service is the classname (symfony 3 style)

#### perform just a few products
```
use \RpayRatePay\DTO\BasketPosition;

$orderDetail = [ instance of \Shopware\Models\Order\Detail ]
$order = [ instance of \Shopware\Models\Order ]

$basketPosition = new BasketPosition($productNumber, $qty);
$basketPosition->setOrderDetail($orderDetail);
$basketPositions[] = $basketPosition;

$basketPosition = new BasketPosition($productNumber, $qty);
$basketPosition->setOrderDetail($orderDetail);
$basketPositions[] = $basketPosition;

[...]

$basketPositions[] = new BasketPosition(BasketPosition::SHIPPING_NUMBER, 1);
 
$requestService->setItems($basketPositions);
$requestService->setOrder($order);
$response = $requestService->doRequest();
```

If you want to deliver/cancel/return the shipping costs of an order, you need to provide `shipping` as a string as `$productNumber`. The `$qty` must be `1`. 
You must not call `setOrderDetail()` on the `$basketPosition`.

Please not that you always have to call `setOrderDetail()` if you want to deliver/cancel/return an product or voucher, except the shipping costs.


#### perform full action
If you want to do a full deliver/cancel/return, just call the following:
```
$order = [ instance of \Shopware\Models\Order ]
$response = $requestService->doFullAction($order);
```
(already delivered/returned/canceled items will be ignored.)

### Response
you will get an `\RatePAY\Model\Response\AbstractResponse` or a boolean as response.
If you get an `AbstractResponse`, just call `isSuccessful()` to verify if the request was successful.
