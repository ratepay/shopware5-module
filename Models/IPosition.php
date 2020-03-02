<?php


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
