<?php


namespace RpayRatePay\Services\Request;


class PaymentCancelService extends AbstractModifyRequest
{
    protected $_subType = 'cancellation';

    protected $updateStock = false;

    protected function getCallName()
    {
        return "paymentChange";
    }

    protected function processSuccess()
    {
        foreach ($this->items as $item) {
            $bind = [
                'cancelled' => $item->cancelled + $item->cancelledItems
            ];
            $this->updatePosition($this->_order->getId(), $item->articlenumber, $bind);
            if ($item->cancelledItems <= 0) {
                continue;
            }

            if ($this->updateStock) {
                $this->updateArticleStock($item->articlenumber, $item->cancelledItems);
            }

            $this->historyLogger->logHistory($this->_order, 'Artikel wurde storniert.', $item->name, $item->articlenumber, $item->cancelledItems);
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
