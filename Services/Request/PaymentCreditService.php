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
        foreach ($this->items as $productNumber => $quantity) {
            $position = $this->getOrderPosition($productNumber);
            //$this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $quantity, 'Nachlass wurde hinzugefügt');
        }
    }
}
