/**
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package pi_ratepay_rate_calculator
 * Code by Ratepay GmbH  <http://www.ratepay.com/>
 */
function changeFirstday(firstday) {
    document.getElementById('paymentFirstday').value = firstday;

    if (firstday == 28) {
        document.getElementById('debitDetails').style.display = 'none';
        document.getElementById('piRpResultContainer').style.display = 'none';
        document.getElementById('changeFirstday').style.display = 'none';
        document.getElementById('changeFirstday2').style.display = 'block';
    } else {
        document.getElementById('debitDetails').style.display = 'block';
        document.getElementById('piRpResultContainer').style.display = 'none';
        document.getElementById('changeFirstday2').style.display = 'none';
        document.getElementById('changeFirstday').style.display = 'block';
    }
}