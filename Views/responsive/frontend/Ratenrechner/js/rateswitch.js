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
    var bankcode = document.getElementById('ratepay_debit_bankcode');
    var accountnumber = document.getElementById('ratepay_debit_accountnumber');

    if (firstday == 28) {
        document.getElementById('debitDetails').style.display = 'none';
        document.getElementById('piRpResultContainer').style.display = 'none';
        document.getElementById('changeFirstday').style.display = 'none';
        document.getElementById('changeFirstday2').style.display = 'block';
        //$('#ratepay_debit_bankcode').clone()

        $(':input#ratepay_debit_bankcode').prop('disabled', true);
        $(':input#ratepay_debit_accountnumber').prop('disabled', true);

    } else {
        document.getElementById('debitDetails').style.display = 'block';
        document.getElementById('piRpResultContainer').style.display = 'none';
        document.getElementById('changeFirstday2').style.display = 'none';
        document.getElementById('changeFirstday').style.display = 'block';

        $(':input#ratepay_debit_bankcode').prop('disabled', false);
        $(':input#ratepay_debit_accountnumber').prop('disabled', false);
    }
}