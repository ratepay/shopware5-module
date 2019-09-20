<?php


namespace RpayRatePay\Helper;


use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\Position\Discount;
use RpayRatePay\Models\Position\Product;
use RpayRatePay\Models\Position\Shipping;
use RpayRatePay\Models\Position\Shipping as ShippingPosition;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class PositionHelper
{

    const MODE_SW_DISCOUNT = [2,3,4];
    const MODE_RP_SHIPPING = 289;
    const MODE_SW_PRODUCT = [0,1];

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

    public static function getPositionClass(Detail $detail)
    {
        /** @var Product $position */
        switch($detail->getMode()) {
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
     * @param PositionStruct|Detail $object
     * @return bool
     */
    public static function isDiscount($object)
    {
        if($object instanceof Detail || $object instanceof PositionStruct) {
            return in_array($object->getMode(), self::MODE_SW_DISCOUNT);
        } else {
            throw new \RuntimeException('the object must be a type of '.Detail::class.' or '.PositionStruct::class);
        }
    }

    /**
     * @param Detail $detail
     * @return AbstractPosition
     */
    public function getPositionForDetail($detail)
    {
        return $this->modelManager->find(self::getPositionClass($detail), $detail->getId());
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
