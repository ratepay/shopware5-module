<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;


interface IPosition
{

    public function getDelivered();
    public function setDelivered($delivered);
    public function getCancelled();
    public function setCancelled($cancelled);
    public function getReturned();
    public function setReturned($returned);

}
