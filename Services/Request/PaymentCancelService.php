<?php


namespace RpayRatePay\Services\Request;


class PaymentCancelService extends AbstractModifyRequest
{
    protected $_subType = 'cancellation';

    protected $updateStock = false;

    protected function getCallName()
    {
        return self::CALL_CHANGE;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $productNumber => $quantity) {
            $position = $this->getOrderPosition($productNumber);
            $position->setCancelled($position->getCancelled() + $quantity);
            $this->modelManager->flush($position);

            if ($this->updateStock) {
                $this->updateArticleStock($productNumber, $quantity);
            }
            $this->historyLogger->logHistory($position, $quantity, 'Artikel wurde storniert.');
        }
    }

    /**
     * @param bool $updateStock
     */
    public function setUpdateStock($updateStock)
    {
        $this->updateStock = $updateStock;
    }

}
