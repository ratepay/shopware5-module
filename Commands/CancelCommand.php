<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
