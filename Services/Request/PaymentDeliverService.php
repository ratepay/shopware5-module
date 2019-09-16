<?php


namespace RpayRatePay\Services\Request;


use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use Shopware\Models\Order\Document\Document;

class PaymentDeliverService extends AbstractModifyRequest
{


    protected function getCallName()
    {
        return 'confirmationDeliver';
    }

    protected function getRequestContent()
    {
        if($this->items == null) {
            throw new \RuntimeException('please set $items with function `setItems()`');
        }
        if($this->_order == null) {
            throw new \RuntimeException('please set $order with function `setOrder()`');
        }

        $requestType = 'shipping';
        if ($this->_order->getPayment()->getName() == PaymentMethods::PAYMENT_INSTALLMENT0 || $this->_order->getPayment()->getName() == PaymentMethods::PAYMENT_RATE) {
            $requestType = 'shippingRate';
        }
        $basketFactory = new BasketArrayBuilder($this->_order, $this->items, $requestType);

        //quantity
        $requestContent = [];
        $requestContent['ShoppingBasket'] = $basketFactory->toArray();

        $documentModel = Shopware()->Models()->getRepository(Document::class); //TODO DI
        $document = $documentModel->findOneBy(['orderId' => $this->_order->getId(), 'type' => 1]);
        if ($document !== null) {
            $dateObject = new \DateTime();
            $currentDate = $dateObject->format('Y-m-d');
            $currentTime = $dateObject->format('H:m:s');
            $currentDateTime = $currentDate . 'T' . $currentTime;

            $requestContent = array_merge($requestContent, [
                'Invoicing' => [
                    'InvoiceId' => $document->getDocumentId(),
                    'InvoiceDate' => $currentDateTime,
                    'DeliveryDate' => $currentDateTime,
                    //'DueDate' => date('Y-m-d\Th:m:s'),
                ]
            ]);
        }
        return $requestContent;
    }

    protected function processSuccess($success = false)
    {

    }

}
