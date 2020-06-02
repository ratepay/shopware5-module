<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services;

use Monolog\Logger;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Request\PaymentCancelService;
use RpayRatePay\Services\Request\PaymentDeliverService;
use RpayRatePay\Services\Request\PaymentReturnService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class OrderStatusChangeService
{
    const MSG_FAILED_SENDING_FULL_RETURN = 'Unable to send full return for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_CANCELLATION = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FAILED_SENDING_FULL_DELIVERY = 'Unable to send full cancellation for order: %d. %s';
    const MSG_FULL_DELIVERY_REJECTED = 'Full delivery request was rejected for order: %d. %s';
    const MSG_FULL_RETURN_REJECTED = 'Full return request was rejected for order: %d. %s';
    const MSG_FULL_CANCELLATION_REJECTED = 'Full cancellation request was rejected for order: %d. %s';


    const CHANGE_DELIVERY = 'CHANGE_DELIVERY';
    const CHANGE_RETURN = 'CHANGE_RETURN';
    const CHANGE_CANCEL = 'CHANGE_CANCEL';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var PaymentDeliverService
     */
    protected $paymentDeliverService;

    /**
     * @var PaymentCancelService
     */
    protected $paymentCancelService;

    /**
     * @var PaymentReturnService
     */
    protected $paymentReturnService;
    /**
     * @var ConfigService
     */
    protected $pluginConfig;

    /**
     * @var ModelManager
     */
    protected $modelManager;
    /**
     * @var PositionHelper
     */
    protected $positionHelper;

    public function __construct(
        ModelManager $modelManager,
        ConfigService $pluginConfig,
        PositionHelper $positionHelper,
        PaymentDeliverService $paymentDeliverService,
        PaymentCancelService $paymentCancelService,
        PaymentReturnService $paymentReturnService,
        Logger $logger
    )
    {
        $this->modelManager = $modelManager;
        $this->pluginConfig = $pluginConfig;
        $this->positionHelper = $positionHelper;
        $this->paymentDeliverService = $paymentDeliverService;
        $this->paymentCancelService = $paymentCancelService;
        $this->paymentReturnService = $paymentReturnService;
        $this->logger = $logger;
    }

    /**
     * Sends Ratepay notification of order status change when new status meets criteria.
     *
     * @param Order $order
     */
    public function informRatepayOfOrderStatusChange(Order $order)
    {
        if (PaymentMethods::exists($order->getPayment()) === false) {
            //payment is not a ratepay order
            return;
        }

        $roundTrips = [
            self::CHANGE_DELIVERY => $this->pluginConfig->getBidirectionalOrderStatus('full_delivery'),
            self::CHANGE_RETURN => $this->pluginConfig->getBidirectionalOrderStatus('full_return'),
            self::CHANGE_CANCEL => $this->pluginConfig->getBidirectionalOrderStatus('full_cancellation'),
        ];

        foreach ($roundTrips as $changeType => $statusId) {
            if ($order->getOrderStatus()->getId() !== $statusId) {
                continue;
            }

            switch ($changeType) {
                case self::CHANGE_DELIVERY:
                    $this->paymentDeliverService->doFullAction($order);
                    break;
                case self::CHANGE_RETURN:
                    $this->paymentReturnService->doFullAction($order);
                    break;
                case self::CHANGE_CANCEL:
                    $this->paymentCancelService->doFullAction($order);
                    break;
            }
        }
    }
}
