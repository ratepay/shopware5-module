/**
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package pi_ratepay_rate_calculator
 * Code by Ratepay GmbH  <http://www.ratepay.com/>
 */
function changeFirstday(firstday) {
    $('#paymentFirstday').val(firstday);

    if (firstday == 28) {
        $('#debitDetails').hide();
        $('#piRpResultContainer').hide();
        $('#changeFirstday').hide();
        $('#changeFirstday2').show();

        $(':input#ratepay_debit_bankcode').prop('disabled', true);
        $(':input#ratepay_debit_accountnumber').prop('disabled', true);

    } else {
        $('#debitDetails').show();
        $('#piRpResultContainer').hide();
        $('#changeFirstday2').hide()
        $('#changeFirstday').show();

        $(':input#ratepay_debit_bankcode').prop('disabled', false);
        $(':input#ratepay_debit_accountnumber').prop('disabled', false);
    }

    if ($('#secondInput').is(':checked')) {
        piRatepayRateCalculatorAction('runtime');
        $('#switchInformation').show();
    } else if ($('#firstInput').is(':checked')) {
        piRatepayRateCalculatorAction('wishrate');
        $('#switchInformation').show();
    }
}