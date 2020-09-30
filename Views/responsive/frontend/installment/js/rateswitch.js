/**
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package pi_ratepay_rate_calculator
 * Code by Ratepay GmbH  <http://www.ratepay.com/>
 */
function changeFirstday(firstday) {

    const BANK_TRANSFER = 28;

    $('#paymentFirstday').val(firstday);
    var button = $('button[type=submit]');

    if (firstday == BANK_TRANSFER) {
        if ($('#debitDetails') !== "") {
            $('#debitDetails').hide();
        }

        $('#piRpResultContainer').hide();
        $('#changeFirstday').hide();
        $('#changeFirstday2').show();

        $(':input#ratepay_debit_accountnumber').prop('disabled', true);
        $("#ratepay_agb").prop('disabled', true);
        $("#paywire").hide();
        $("#wicAGB").hide();
        button.removeAttr('disabled');
        button.removeAttr('title');
        button.css({ opacity: 1.0 });


    } else {
        if ($('#debitDetails') !== "") {
            $('#debitDetails').show();
        }
        $('#piRpResultContainer').hide();
        $('#changeFirstday2').hide()
        $('#changeFirstday').show();
        $("#paywire").show();
        $("#wicAGB").show();

        $(':input#ratepay_debit_accountnumber').prop('disabled', false);

        $("#ratepay_agb").prop('disabled', false);
        button.attr('disabled', 'disabled');
        button.attr('title', errorMessageAcceptSepaAGB);
        button.css({ opacity: 0.5 });
    }

    if ($('#rp-rate-value').val() > 1) {
        piRatepayRateCalculatorAction('rate', 0);
    } else {
        month = $('#month').val();
        piRatepayRateCalculatorAction('runtime', month);
    }
}
