<?php

namespace RpayRatePay\Exception;

use Exception;

class NoProfileFoundException extends Exception
{

    public function __construct()
    {
        parent::__construct(Shopware()->Snippets()->getNamespace('backend/ratepay')->get('ErrorCantFindProfile'));
    }
}
