<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 05.09.18
 * Time: 10:17
 */
namespace  RpayRatePay\Component\Service;

class Logger
{
    const SERVICE_NAME = 'rpayratepay.logger';

    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Shopware\Components\Logger
     */
    public static function singleton()
    {
        return Shopware()->Container()->get('pluginlogger');
        // return Shopware()->Container()->get('logger');
    }
}
