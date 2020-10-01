<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Enum;


final class OrderStatus extends Enum
{

    const STATUS = [
        'state' => [
            255 => ['name' => 'ratepay_partly_return', 'description' => 'Teil-(Retoure)'],
            265 => ['name' => 'ratepay_partly_cancel', 'description' => 'Teil-(Storno)'],
        ],
        'position' => [
            155 => ['name' => 'ratepay_return', 'description' => 'Retourniert'],
        ],
        'payment' => [
            155 => ['name' => 'ratepay_payment_via_ratepay', 'description' => 'Zahlungsabwicklung durch RatePAY'],
        ]
    ];
}
