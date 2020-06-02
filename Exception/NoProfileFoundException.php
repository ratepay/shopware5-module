<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Exception;

use Exception;

class NoProfileFoundException extends Exception
{

    public function __construct()
    {
        parent::__construct(Shopware()->Snippets()->getNamespace('backend/ratepay')->get('ErrorCantFindProfile'));
    }
}
