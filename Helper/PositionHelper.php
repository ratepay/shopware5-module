<?php


namespace RpayRatePay\Helper;


use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\Position\Discount;
use RpayRatePay\Models\Position\Product;
use RpayRatePay\Models\Position\Shipping;
use RpayRatePay\Models\Position\Shipping as ShippingPosition;
use RuntimeException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class PositionHelper
{

    const MODE_SW_DISCOUNT = [2, 3, 4];
    const MODE_RP_SHIPPING = 289;
    const MODE_SW_PRODUCT = [0, 1];

    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct(
        ModelManager $modelManager
    )
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param PositionStruct|Detail $object
     * @return bool
     */
    public static function isDiscount($object)
    {
        if ($object instanceof Detail || $object instanceof PositionStruct) {
            return in_array($object->getMode(), self::MODE_SW_DISCOUNT);
        } else {
            throw new RuntimeException('the object must be a type of ' . Detail::class . ' or ' . PositionStruct::class);
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isOrderComplete(Order $order)
    {
        return $this->doesOrderHasOpenPositions($order) === false;
    }

    /**
     * if $items is provided, the item openQuantity of each position will be reduced by the given quantity
     *
     * @param Order $_order
     * @param BasketPosition[] $items key:productNumber/value:quantity
     * @return bool
     */
    public function doesOrderHasOpenPositions(Order $_order, $items = [])
    {
        /** @var Detail $detail */
        foreach ($_order->getDetails() as $detail) {
            $position = $this->getPositionForDetail($detail);
            $number = $detail->getArticleNumber();
            /** @var  $basketPosition */
            $quantity = isset($items[$number]) ? $items[$number]->getQuantity() : 0;
            if (($position->getOpenQuantity() - $quantity) !== 0) {
                return true;
            }
        }
        $shippingPosition = $this->getShippingPositionForOrder($_order);
        if ($shippingPosition) {
            $quantity = isset($items[BasketPosition::SHIPPING_NUMBER]) ? $items[BasketPosition::SHIPPING_NUMBER]->getQuantity() : 0;
            if (($shippingPosition->getOpenQuantity() - $quantity) !== 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Detail $detail
     * @return AbstractPosition
     */
    public function getPositionForDetail($detail)
    {
        return $this->modelManager->find(self::getPositionClass($detail), $detail->getId());
    }

    public static function getPositionClass(Detail $detail)
    {
        /** @var Product $position */
        switch ($detail->getMode()) {
            case self::MODE_SW_PRODUCT[0]: //product
            case self::MODE_SW_PRODUCT[1]: //premium product
                return Product::class;
                break;
            case self::MODE_SW_DISCOUNT[0]: //voucher
            case self::MODE_SW_DISCOUNT[1]: //IS_REBATE = rabatt
            case self::MODE_SW_DISCOUNT[2]: //IS_SURCHARGE_DISCOUNT //IST ZUSCHLAGSRABATT
                return Discount::class;
                break;
            case self::MODE_RP_SHIPPING:
                return ShippingPosition::class;
            default:
                return null;
        }
    }

    /**
     * @param Order $order
     * @return ShippingPosition
     */
    public function getShippingPositionForOrder(Order $order)
    {
        return $this->modelManager->find(ShippingPosition::class, $order->getId());
    }

}
