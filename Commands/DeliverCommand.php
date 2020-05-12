<?php


namespace RpayRatePay\Commands;


use RpayRatePay\Services\Request\PaymentDeliverService;
use Shopware\Components\Model\ModelManager;

class DeliverCommand extends AbstractCommand
{

    /**
     * @var PaymentDeliverService
     */
    private $deliverService;

    public function __construct(ModelManager $modelManager, PaymentDeliverService $deliverService, $name = null)
    {
        parent::__construct($modelManager, $name);
        $this->deliverService = $deliverService;
    }

    protected function getRequestService()
    {
        return $this->deliverService;
    }
}
