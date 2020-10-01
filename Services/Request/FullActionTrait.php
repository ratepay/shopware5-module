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

    /**
     * if $details is provided, only the provided details will be processed.
     * if $details is provided, you must provide a element in the $details array with the value "shipping" to perform the shipping position.
     * @param Order $order
     * @param array|null $details
     * @return bool
     */
    public function doFullAction(Order $order, array $details = null)
    {
        $sendToGateway = false;
        $basketArrayBuilder = new BasketArrayBuilder($order);

        foreach ($details ?? $order->getDetails()->getValues() as $detail) {
            if ($detail === BasketPosition::SHIPPING_NUMBER) {
                continue;
            }
            $position = $this->positionHelper->getPositionForDetail($detail);
            // openQuantity should be the orderedQuantity.
            // To prevent unexpected errors we will only submit the openQuantity
            $qty = $this->getOpenQuantityForFullAction($position);
            if ($qty > 0) {
                $basketArrayBuilder->addItem($detail, $qty);
                $sendToGateway = true;
            }
        }
        // only perform shipping position if no details has been provided
        if ($details === null || in_array(BasketPosition::SHIPPING_NUMBER, $details, true)) {
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
        }

        if ($sendToGateway === false) {
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
