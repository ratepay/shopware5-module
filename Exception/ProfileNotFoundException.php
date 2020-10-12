<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Shopware_Plugins_Frontend_RpayRatePay_Exception_ProfileNotFoundException extends Exception
{

    public function __construct()
    {
        parent::__construct(Shopware()->Snippets()->getNamespace('ratepay/errors')->get('profileNotFound'));
    }
}
