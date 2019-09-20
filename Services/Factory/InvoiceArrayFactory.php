<?php


namespace RpayRatePay\Services\Factory;


use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;

class InvoiceArrayFactory
{
    const ARRAY_KEY = 'Invoice';
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        ModelManager $modelManager
    )
    {
        $this->modelManager = $modelManager;
    }

    public function getData(Order $order) {
        $documentModel = $this->modelManager->getRepository(Document::class); //TODO DI
        $document = $documentModel->findOneBy(['orderId' => $order->getId(), 'type' => 1]);
        if ($document !== null) {
            $dateObject = new \DateTime();
            $currentDate = $dateObject->format('Y-m-d');
            $currentTime = $dateObject->format('H:m:s');
            $currentDateTime = $currentDate . 'T' . $currentTime;

            return [
                'InvoiceId' => $document->getDocumentId(),
                'InvoiceDate' => $currentDateTime,
                'DeliveryDate' => $currentDateTime,
                //'DueDate' => date('Y-m-d\Th:m:s'),
            ];
        }
        return null;
    }
}
