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
        foreach ($this->items as $item) {
            $position = $this->getOrderPosition($item->getProductNumber());
            $position->setCancelled($position->getCancelled() + $item->getQuantity());
            $this->modelManager->flush($position);

            if ($this->updateStock) {
                $this->updateArticleStock($item->getProductNumber(), $item->getQuantity());
            }
            $this->historyLogger->logHistory($position, $item->getQuantity(), 'Artikel wurde storniert.');
        }
        parent::processSuccess();
    }

}
