<?php

class Shopware_Plugins_Frontend_RpayRatePay_Exception_ProfileNotFoundException extends Exception
{

    public function __construct()
    {
        parent::__construct(Shopware()->Snippets()->getNamespace('ratepay/errors')->get('profileNotFound'));
    }
}
