<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

    /**
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     *
     * @package pi_ratepay_rate_calculator
     * Code by Ratepay GmbH  <http://www.ratepay.com/>
     */

    $ShopPort = "//";
    $pi_ratepay_rate_calc_path = $ShopPort . Shopware()->Config()->get('basepath') . '/engine/Shopware/Plugins/' . Shopware()->Plugins()->Frontend()->RpayRatePay()->getSource() . '/Frontend/RpayRatePay/Views/responsive/frontend/installment/';
