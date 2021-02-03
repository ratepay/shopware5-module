# Ratepay GmbH - Shopware Payment Module
============================================

|Module | Ratepay Module for Shopware
|------|----------
|Shop Version | `5.5.0` - `5.7.x`
|Version | `6.0.0`
|Link | http://www.ratepay.com
|Mail | integration@ratepay.com
|Full Documentation | https://ratepay.gitbook.io/shopware5/

## Installation

### via packagist (recommenced)
This is only possible if you use the [composer setup of shopware](https://developers.shopware.com/developers-guide/shopware-composer/)
1. execute `composer require ratepay/shopware5-module` in your project directory
3. Log into your Shopware-backend
4. Install & configure the module

### via Shopware store (or GitHub [release download](https://github.com/ratepay/shopware5-module/releases))
1. Download the plugin from the [Shopware store](https://store.shopware.com/rpay00625f/ratepay-payment-plugin-for-shopware-5.html)
2. Upload it via the Plugin Manager or put it into the folder `custom/plugins/RpayRatePay`
3. Log into your Shopware-backend
4. Install & configure the module

## Changelog
please have a look into plugin.xml

## Shopware CLI Commands
You can use the Shopware CLI to perform operations on Ratepay orders.

All commands have the same structure:

``` 
./bin/console ratepay:<operation> <order> [<orderDetail>] [<qty>] 
```

| Name          | Description                                                                                                                                                                                     | required |
|---------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| `operation`   | one of `deliver`, `return`, `cancel`                                                                                                                                                            | Yes      |
| `order`       | the `order id`, `order number` or the ` transaction id` of the order                                                                                                                        | Yes      |
| `orderDetail` | the `detail id` or `detail number` which has to be performed. If not provided, all items of a order will be selected.<br>Use `shipping` to perform the action on the shipping costs. | No       |
| `qty`         | the quantity which has to be performed. If not provided, the original ordered quantity will be used                                                                                          | No       |

### example operation `deliver`
all elements of the order (with the id `125`) will be delivered
``` 
./bin/console ratepay:deliver 125
```
### example operation `return`
`2` elements with item-number `SW0001` of the order (with the transaction-id `54-214XXXXXX2133`) will be returned
``` 
./bin/console ratepay:return 54-214XXXXXX2133 SW0001 2
```
### example operation `cancel`
all elements with item-number `SW0001` of the order (with the order-number `200012`) will be canceled
``` 
./bin/console ratepay:cancel 200012 SW0001
```

## Request services
There are a three request services registered in the container:
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

Please note that you always have to call `setOrderDetail()` if you want to deliver/cancel/return a product or voucher, except the shipping costs.


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

If you get a boolean with the value `true` the operation has been registered to in the database, but has not been sent to the gateway.
This will happen, if the order is an installment and there an open (not delivered/canceled) items in the order.
