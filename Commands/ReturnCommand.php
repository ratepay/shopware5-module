<?php


namespace RpayRatePay\Commands;


use RpayRatePay\Services\Request\PaymentReturnService;
use Shopware\Components\Model\ModelManager;

class ReturnCommand extends AbstractCommand
{

    /**
     * @var PaymentReturnService
     */
    private $returnService;

    public function __construct(ModelManager $modelManager, PaymentReturnService $returnService, $name = null)
    {
        parent::__construct($modelManager, $name);
        $this->returnService = $returnService;
    }

    protected function getRequestService()
    {
        return $this->returnService;
    }
}
