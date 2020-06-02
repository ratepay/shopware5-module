<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


class PaymentCancelService extends AbstractModifyRequest
{
    use FullActionTrait;

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
