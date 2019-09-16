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
        return "paymentChange";
    }

    protected function processSuccess()
    {
        //TODO move content to parent class
        foreach($this->items as $item) {
            if ($item->returnedItems <= 0) {
                continue;
            }

            $this->updatePosition($this->_order->getId(), $item->articlenumber, [
                'returned' => $item->returned + $item->returnedItems
            ]);

            $this->historyLogger->logHistory($this->_order, 'Artikel wurde retourniert.', $item->name, $item->articlenumber, $item->returnedItems);
            if ($this->updateStock) {
                $this->updateArticleStock($item->articlenumber, $item->returnedItems);
            }
        }
    }

    public function setUpdateStock($updateStock)
    {
        $this->updateStock = $updateStock;
    }
}
