<?php


namespace RpayRatePay\Services\Request;

use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Models\ProfileConfig;
use Shopware\Models\Order\Order;

class PaymentConfirmService extends AbstractRequest
{

    /**
     * @var Order
     */
    protected $order;

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
            'OrderId' => /*$this->order->getNumber()*/
                null, //TODO currently not transmitted
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
    }

    /**
     * @param $isBackend
     * @return ProfileConfig
     */
    protected function getProfileConfig()
    {
        return $this->profileConfigService->getProfileConfig(
            $this->order->getBilling()->getCountry()->getIso(),
            $this->order->getShop()->getId(),
            $this->order->getAttribute()->getRatepayBackend() == 1,
            $this->order->getPayment()->getName() == PaymentMethods::PAYMENT_INSTALLMENT0
        );
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}
