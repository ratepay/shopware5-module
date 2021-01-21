<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Services\Request;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Logger\RequestLogger;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class PaymentConfirmService extends AbstractRequest
{

    /**
     * @var Order
     */
    protected $order;
    /**
     * @var ProfileConfigService
     */
    private $profileConfigService;
    /**
     * @var PaymentDeliverService
     */
    private $paymentDeliverService;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        RequestLogger $requestLogger,
        ProfileConfigService $profileConfigService,
        PaymentDeliverService $paymentDeliverService
    )
    {
        parent::__construct($db, $configService, $requestLogger);
        $this->profileConfigService = $profileConfigService;
        $this->paymentDeliverService = $paymentDeliverService;
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    protected function getCallName()
    {
        return self::CALL_PAYMENT_CONFIRM;
    }

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
        $data = parent::getRequestHead($profileConfig);
        $data['External'] = [
            'OrderId' => $this->order->getNumber(),
            'MerchantConsumerId' => $this->order->getCustomer()->getNumber()
        ];
        $data['TransactionId'] = $this->order->getTransactionId();
        return $data;
    }

    /**
     * @return array
     */
    protected function getRequestContent()
    {
        return null; // we do not need a content for confirming a payment
    }

    protected function processSuccess()
    {
        if ($this->configService->isEsdAutoDeliver() &&
            $this->order->getPayment()->getName() !== PaymentMethods::PAYMENT_PREPAYMENT
        ) {
            $itemsToDeliver = [];
            /** @var Detail $detail */
            foreach ($this->order->getDetails() as $detail) {
                if ($detail->getEsdArticle() === 1) {
                    $position = new BasketPosition($detail->getNumber(), $detail->getQuantity());
                    $position->setOrderDetail($detail);
                    $itemsToDeliver[] = $position;
                }
            }
            if (count($itemsToDeliver)) {
                $this->paymentDeliverService->setOrder($this->order);
                $this->paymentDeliverService->setItems($itemsToDeliver);
                $this->paymentDeliverService->doRequest();
            }
        }
    }

    protected function getProfileConfig()
    {
        $orderAttribute = $this->order->getAttribute();
        return $orderAttribute ? $this->profileConfigService->getProfileConfigById($orderAttribute->getRatepayProfileId()) : null;
    }
}
