<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
