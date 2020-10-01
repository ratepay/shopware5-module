<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Factory;


use DateTimeInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;

class InvoiceArrayFactory
{
    const ARRAY_KEY = 'Invoicing';
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

    public function getData(Order $order)
    {
        $documentModel = $this->modelManager->getRepository(Document::class); //TODO DI
        $document = $documentModel->findOneBy(['orderId' => $order->getId(), 'type' => 1]);
        if ($document !== null) {
            /** @var DateTimeInterface $dateObject */
            $dateObject = $document->getDate();
            $currentDate = $dateObject->format('Y-m-d');
            $currentTime = $dateObject->format('H:i:s');
            $currentDateTime = $currentDate . 'T' . $currentTime;

            return [
                'InvoiceId' => $document->getDocumentId(),
                'InvoiceDate' => $currentDateTime,
                'DeliveryDate' => $currentDateTime
            ];
        }
        return null;
    }
}
