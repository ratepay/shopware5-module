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
        foreach ($this->items as $item) {
            $position = $this->getOrderPosition($item->getProductNumber());
            $position->setReturned($position->getReturned() + $item->getQuantity());
            $this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $item->getQuantity(), 'Artikel wurde retourniert.');
            if ($this->updateStock) {
                $this->updateArticleStock($item->getProductNumber(), $item->getQuantity());
            }
        }
    }

    public function setUpdateStock($updateStock)
    {
        $this->updateStock = $updateStock;
    }
}
