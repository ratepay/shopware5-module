<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


class PaymentCreditService extends AbstractAddRequest
{

    protected $_subType = 'credit';

    /**
     * @return string
     */
    protected function getCallName()
    {
        return self::CALL_CHANGE;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $basketPosition) {
            $position = $this->getOrderPosition($basketPosition);
            $position->setDelivered($position->getOrderedQuantity());
            $this->modelManager->flush($position);

            $this->historyLogger->logHistory($position, $basketPosition->getQuantity(), 'Nachlass wurde hinzugefügt');
        }
    }
}
