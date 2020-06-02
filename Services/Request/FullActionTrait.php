<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;


use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\Position\AbstractPosition;
use Shopware\Models\Order\Order;

/**
 * @property PositionHelper $positionHelper
 * @property bool $isRequestSkipped
 * @method setOrder(Order $order)
 * @method setItems(BasketArrayBuilder|BasketPosition[] $items)
 * @method doRequest()
 */
trait FullActionTrait
{

    public function doFullAction(Order $order)
    {
        $sendToGateway = false;
        $basketArrayBuilder = new BasketArrayBuilder($order);
        foreach ($order->getDetails() as $detail) {
            $position = $this->positionHelper->getPositionForDetail($detail);
            // openQuantity should be the orderedQuantity.
            // To prevent unexpected errors we will only submit the openQuantity
            $qty = $this->getOpenQuantityForFullAction($position);
            if ($qty > 0) {
                $basketArrayBuilder->addItem($detail, $qty);
                $sendToGateway = true;
            }
        }
        $shippingPosition = $this->positionHelper->getShippingPositionForOrder($order);
        if ($shippingPosition) {
            // openQuantity should be the orderedQuantity.
            // To prevent unexpected errors we will only submit the openQuantity
            $qty = $this->getOpenQuantityForFullAction($shippingPosition);
            if ($qty === 1) {
                $basketArrayBuilder->addShippingItem();
                $sendToGateway = true;
            }
        }

        if ($sendToGateway == false) {
            $this->isRequestSkipped = true;
            return true;
        }

        $this->setOrder($order);
        $this->setItems($basketArrayBuilder);
        return $this->doRequest();
    }

    protected function getOpenQuantityForFullAction(AbstractPosition $position)
    {
        return $position->getOpenQuantity();
    }

}
