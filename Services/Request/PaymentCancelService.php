<?php


namespace RpayRatePay\Services\Request;


class PaymentCancelService extends AbstractModifyRequest
{
    protected $_subType = 'cancellation';

    protected $updateStock = false;

    /**
     * @param bool $updateStock
     */
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
            $position->setCancelled($position->getCancelled() + $basketPosition->getQuantity());
            $this->modelManager->flush($position);

            if ($this->updateStock) {
                $this->updateArticleStock($basketPosition);
            }
            $this->historyLogger->logHistory($position, $basketPosition->getQuantity(), 'Artikel wurde storniert.');
        }
        parent::processSuccess();
    }

}
