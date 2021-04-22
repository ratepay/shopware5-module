<?php

use RpayRatePay\Models\OrderHistory;

/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Shopware_Controllers_Backend_RatepayOrderHistory extends Shopware_Controllers_Backend_Application
{

    protected $model = OrderHistory::class;
    protected $alias = 'history';

}
