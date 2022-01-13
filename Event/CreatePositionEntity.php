<?php

namespace RpayRatePay\Event;


use Enlight_Event_EventArgs;
use RpayRatePay\Models\Position\AbstractPosition;
use Shopware\Models\Order\Detail;

/**
 * @method AbstractPosition getReturn()
 */
class CreatePositionEntity extends Enlight_Event_EventArgs
{

    /**
     * @var \Shopware\Models\Order\Detail
     */
    private $detail;

    public function __construct(Detail $detail)
    {
        parent::__construct([]);
        $this->detail = $detail;
    }

    /**
     * @return \Shopware\Models\Order\Detail
     */
    public function getDetail()
    {
        return $this->detail;
    }

}
