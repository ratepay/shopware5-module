<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


use RpayRatePay\Models\Position\AbstractPosition;

class PaymentReturnService extends AbstractModifyRequest
{
    use FullActionTrait;

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

    protected function getOpenQuantityForFullAction(AbstractPosition $position)
    {
        return $position->getDelivered() - $position->getReturned();
    }

}
