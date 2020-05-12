<?php


namespace RpayRatePay\Commands;


use RpayRatePay\Services\Request\PaymentCancelService;
use Shopware\Components\Model\ModelManager;

class CancelCommand extends AbstractCommand
{

    /**
     * @var PaymentCancelService
     */
    private $cancelService;

    public function __construct(ModelManager $modelManager, PaymentCancelService $cancelService, $name = null)
    {
        parent::__construct($modelManager, $name);
        $this->cancelService = $cancelService;
    }

    protected function getRequestService()
    {
        return $this->cancelService;
    }
}
