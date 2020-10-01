<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
