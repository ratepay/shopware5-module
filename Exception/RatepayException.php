<?php


namespace RpayRatePay\Exception;


use Throwable;

class RatepayException extends \Exception
{

    private $context;

    public function __construct($message = "", $code = 0, Throwable $previous = null, $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

}