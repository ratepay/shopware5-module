<?php


namespace RpayRatePay\Services\Logger;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @deprecated unknown
 */
class FileLogger extends Logger
{

    const FILENAME = 'ratepay.log';

    public function __construct($logDir)
    {
        parent::__construct('ratepay', [], []);
        $this->pushHandler(new StreamHandler($logDir . '/' . self::FILENAME, Logger::DEBUG));
    }
}
