<?php


namespace RpayRatePay\Services\Request;


class PaymentCreditService extends AbstractModifyRequest
{

    protected $_subType = 'credit';

    /**
     * @return string
     */
    protected function getCallName()
    {
        return self::CALL_CHANGE;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $item) {
            $position = $this->getOrderPosition($item->getProductNumber());
            $position->setDelivered($position->getOrderedQuantity());
            $this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $item->getQuantity(), 'Nachlass wurde hinzugefügt');
        }
    }
}
