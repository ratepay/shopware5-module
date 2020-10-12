/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
