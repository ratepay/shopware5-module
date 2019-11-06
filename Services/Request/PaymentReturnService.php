<?php


namespace RpayRatePay\Services\Request;


class PaymentReturnService extends AbstractModifyRequest
{

    protected $_subType = 'return';
    /**
     * @var boolean
     */
    protected $updateStock;

    public function setUpdateStock($updateStock)
    {
        $this->updateStock = $updateStock;
    }

    protected function getCallName()
    {
        return self::CALL_CHANGE;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $basketPosition) {
            $position = $this->getOrderPosition($basketPosition);
            $position->setReturned($position->getReturned() + $basketPosition->getQuantity());
            $this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $basketPosition->getQuantity(), 'Artikel wurde retourniert.');
            if ($this->updateStock) {
                $this->updateArticleStock($basketPosition);
            }
        }
        parent::processSuccess();
    }
}
