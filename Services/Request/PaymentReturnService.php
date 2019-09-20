<?php


namespace RpayRatePay\Services\Request;


class PaymentReturnService extends AbstractModifyRequest
{

    protected $_subType = 'return';
    /**
     * @var boolean
     */
    protected $updateStock;

    protected function getCallName()
    {
        return self::CALL_CHANGE;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $productNumber => $quantity) {
            $position = $this->getOrderPosition($productNumber);
            $position->setReturned($position->getReturned() + $quantity);
            $this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $quantity, 'Artikel wurde retourniert.');
            if ($this->updateStock) {
                $this->updateArticleStock($productNumber, $quantity);
            }
        }
    }

    public function setUpdateStock($updateStock)
    {
        $this->updateStock = $updateStock;
    }
}
