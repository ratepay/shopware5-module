/*
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

function changeDetails() {

    if (document.getElementById('rp-show-installment-plan-details').style.display === 'none') {
        document.getElementById('rp-show-installment-plan-details').style.display = 'block';
        document.getElementById('rp-hide-installment-plan-details').style.display = 'none';
        document.getElementById('rp-installment-plan-details').style.display = 'none';
        document.getElementById('rp-installment-plan-no-details').style.display = 'block';
    } else {
        document.getElementById('rp-hide-installment-plan-details').style.display = 'block';
        document.getElementById('rp-show-installment-plan-details').style.display = 'none';
        document.getElementById('rp-show-installment-plan-details').style.display = 'none';
        document.getElementById('rp-installment-plan-details').style.display = 'block';
        document.getElementById('rp-installment-plan-no-details').style.display = 'none';
    }

}
