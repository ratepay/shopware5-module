<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/../../../../../../../../tests/Functional/bootstrap.php';

Shopware()->Loader()->registerNamespace('RpayRatePay', __DIR__ . '/../../');
Shopware()->Loader()->registerNamespace('RatePAY', __DIR__ . '/../../Component/Library/src/');
