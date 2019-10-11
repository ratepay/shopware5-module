;(function() {
    if (typeof window.installmentCalculator == 'undefined') {
        return;
    }
    jQuery(function () {
        jQuery(document).on('click', '.rp-btn-runtime', function (event) {
            event.preventDefault();
            callInstallmentPlan('time', jQuery(this).data().bind);
            // marking and de-marking of buttons
            jQuery('.rp-btn-runtime').removeClass('btn-info');
            jQuery('.rp-btn-rate').removeClass('btn-info');
            jQuery('#rp-rate-value').val("");
            jQuery(this).addClass('btn-info');
        });

        jQuery(document).on('click', '.rp-btn-rate', function (event) {
            event.preventDefault();
            callInstallmentPlan('rate', jQuery('#rp-rate-value').val());
            // de-marking of buttons
            jQuery('.rp-btn-runtime').removeClass('btn-info');
        });

        jQuery(document).on('keyup', '#rp-rate-value', function (event) {
            event.preventDefault();
            if (!validateValue(jQuery('#rp-rate-value').val())) {
                jQuery('.rp-btn-rate').prop('disabled', true);
            } else {
                jQuery('.rp-btn-rate').prop('disabled', false);
            }
        });

        jQuery(document).on("click", "#rp-show-installment-plan-details", function (event) {
            event.preventDefault();
            jQuery('.rp-installment-plan-details').show();
            jQuery('.rp-installment-plan-no-details').hide();
            jQuery(this).hide();
            jQuery('#rp-hide-installment-plan-details').show();
        });

        jQuery(document).on("click", "#rp-hide-installment-plan-details", function (event) {
            event.preventDefault();
            jQuery('.rp-installment-plan-details').hide();
            jQuery('.rp-installment-plan-no-details').show();
            jQuery(this).hide();
            jQuery('#rp-show-installment-plan-details').show();
        });


        jQuery("#rp-payment-firstday").val((rpDirectDebitAllowed == "1") ? rpDirectDebitFirstday : rpBankTransferFirstday);

        // initialize visibility

        // starting with hidden sepa form (visible a after rate calculation)
        jQuery('.rp-sepa-form').hide();
        jQuery('.rp-payment-type-switch').hide();

        if (rpDirectDebitAllowed == "1") {
            jQuery('#rp-payment-type').val("DIRECT-DEBIT");
        } else {
            // if direct debit is not allowed 'BANK_TRANSFER' is set by default
            jQuery('#rp-payment-type').val("BANK-TRANSFER");
        }

        // show sepa agreement
        /*jQuery('#rp-show-sepa-agreement').click(function() {
            jQuery(this).hide();
            jQuery('#rp-sepa-agreement').show();
        });*/

        // show bank code if account number is entered
        /*jQuery('#rp-iban-account-number').keyup(function() {
            switchBankCodeInput(jQuery(this));
        });*/

        // hide sepa form
        jQuery(document).on('click', '#rp-switch-payment-type-bank-transfer', function (event) {
            event.preventDefault();
            switchPaymentType("bank-transfer");
        });

        // show sepa form
        jQuery(document).on('click', '#rp-switch-payment-type-direct-debit', function (event) {
            event.preventDefault();
            switchPaymentType("direct-debit");
        });

    });

    function callInstallmentPlan(calcType, calcValue) {
        var params = "?"
            + "calculationAmount=" + jQuery('#rp-calculation-amount').val()
            + "&calculationValue=" + calcValue
            + "&calculationType=" + calcType
            + "&paymentFirstday=" + jQuery("#rp-payment-firstday").val();

        jQuery.ajax(rpInstallmentController + params)
            .done(function (result) {
                // show filled calculation plan template
                jQuery('#rpResultContainer').html(result);

                if (rpDirectDebitAllowed == "1" && jQuery("#rp-payment-type").val() == "DIRECT-DEBIT") {
                    jQuery('.rp-sepa-form').show();
                }

                jQuery('.rp-payment-type-switch').hide();
                // if payment type bank transfer is allowed show switch
                if (rpBankTransferAllowed == "1") {
                    if (jQuery("#rp-payment-type").val() == "DIRECT-DEBIT") {
                        jQuery('#rp-switch-payment-type-bank-transfer').show();
                    } else if (rpDirectDebitAllowed == "1") {
                        jQuery('#rp-switch-payment-type-direct-debit').show();
                    }
                }

                jQuery('#rp-calculation-type').val(calcType);
                jQuery('#rp-calculation-value').val(calcValue);
            })
            .fail(function () {
                alert("error");
            });
    }

    function validateValue(value) {
        if (value.length == 0) {
            return false;
        }
        if (!jQuery.isNumeric(value)) {
            return false;
        }

        return true;
    }

    // show bank code if account number is entered
    function switchBankCodeInput(element) {
        if (!jQuery.isNumeric(element.val()) || element.val() == "") {
            jQuery('#rp-form-bank-code').hide();
            jQuery('#rp-bank-code').prop('disabled', true);
        } else {
            jQuery('#rp-form-bank-code').show();
            jQuery('#rp-bank-code').prop('disabled', false);
        }
    }

// switch between installment paymenttypes
    function switchPaymentType(paymentType) {
        if (paymentType == "bank-transfer") {
            jQuery('.rp-sepa-form').hide();
            jQuery('#rp-switch-payment-type-bank-transfer').hide();
            jQuery('#rp-switch-payment-type-direct-debit').show();
            jQuery("#rp-payment-type").val("BANK-TRANSFER");
            jQuery("#rp-payment-firstday").val(rpBankTransferFirstday);
        } else {
            jQuery('.rp-sepa-form').show();
            //jQuery('#rp-sepa-agreement').hide();
            jQuery('#rp-switch-payment-type-direct-debit').hide();
            jQuery('#rp-switch-payment-type-bank-transfer').show();
            jQuery("#rp-payment-type").val("DIRECT-DEBIT");
            jQuery("#rp-payment-firstday").val(rpDirectDebitFirstday);
        }
        // After changing payment type, re-call of installment plan because of changed firstday
        callInstallmentPlan(jQuery('#rp-calculation-type').val(), jQuery('#rp-calculation-value').val());
    }
})();
