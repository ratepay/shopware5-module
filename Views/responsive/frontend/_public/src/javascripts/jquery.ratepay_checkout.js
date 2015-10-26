;
(function ($, undefined) {

    /** Plugin constructor */
    $.plugin('RpayRatePay', {
        init: function () {
            var me = this;

            me.$checkoutButton = $('button[form=confirm--form]');
            /*me.$checkoutButton = $('button[form=confirm--form]');*/
            me.registerEvents();

            /* exception if user age is not valid*/
            if(ratepayAgeNotValid == true) {
                $("#ratepay_error").text(errorMessageAgeNotValid);
                $("#ratepay_error").parent().removeClass("is--hidden");
            }

            /* exception if no rate is calculated */
            if(ratepayCalcRateError == true) {
                $("#ratepay_error").text(errorMessageCalcRate);
                $("#ratepay_error").parent().removeClass("is--hidden");
            }

            if(isDebitPayment == true){
                /* Disable confirmation button if sepadirectdebit tac are not checked */
                var button = $('button[type=submit]');

                button.attr('disabled', 'disabled');
                button.css({ opacity: 0.5 });
                button.attr('title', errorMessageAcceptSepaAGB);
                $('#ratepay_agb').click(function () {
                    if ($(this).prop('checked')) {
                        button.removeAttr('disabled');
                        button.removeAttr('title');
                        button.css({ opacity: 1.0 });
                    } else {
                        button.attr('disabled', 'disabled');
                        button.attr('title', errorMessageAcceptSepaAGB);
                        button.css({ opacity: 0.5 });
                    }
                });

                /* Handle BIC Field ( only fade in for AT ) */

                var blzInput       = $(":input#ratepay_debit_bankcode");
                var blzInputLabel  = $("label[for='ratepay_debit_bankcode']");
                var accNumberInput = $(":input#ratepay_debit_accountnumber");

                blzInput.prop('disabled', true);
                blzInput.hide();
                blzInputLabel.hide();

                accNumberInput.keyup(function () {
                    if ($(this).val().match(/^\d+$/)) {
                        blzInput.prop('disabled', false);
                        blzInput.show();
                        blzInputLabel.show();
                    } else if ($(this).val().match(/at/i)) {
                        blzInput.prop('disabled', false);
                        blzInput.show();
                        blzInputLabel.show();
                    }
                    else {
                        blzInput.prop('disabled', true);
                        blzInput.hide();
                        blzInputLabel.hide();
                    }
                })
            }

        },

        /**
         * Helper method to register the needed events
         */
        registerEvents: function () {
            var me = this;
            me._on(me.$checkoutButton, 'click', $.proxy(me.onCheckoutButtonClick, me));
        },

        /**
         * This method is called when the user click on checkout button in registration page
         *
         * @param event
         */
        onCheckoutButtonClick: function (event) {

            var me = this;

            /* returns correct YYYY-MM-dd dob */
            Date.prototype.yyyymmdd = function () {
                var yyyy = this.getFullYear().toString();
                var mm = (this.getMonth() + 1).toString();
                var dd = this.getDate().toString();
                return yyyy + '-' + (mm[1] ? mm : "0" + mm[0]) + '-' + (dd[1] ? dd : "0" + dd[0]);
            };

            /* returns age */
            function getAge(dateString) {
                var today = new Date();
                var birthDate = new Date(dateString);
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age;
            }


            if ($('input#sAGB').is(':checked')) {

                var requestParams = 'userid=' + userId;
                var dob = false;
                var userUpdate = true;
                var error = false;
                var errorMessage = errorMessageDataComplete;
                /** show the modal window */
                $("div.ratepay-overlay").show();

                /* handle all normal inputs */
                $('input[id^="ratepay_"]').each(function () {
                    if ($(this).attr('id') == 'ratepay_debit_accountnumber' || $(this).attr('id') == 'ratepay_debit_bankcode')
                    {
                        requestParams += '&' + $(this).attr('id') + '=' + $(this).val().replace(/ /g, '');
                    } else {
                        requestParams += '&' + $(this).attr('id') + '=' + $(this).val();
                    }
                    if ($(this).val() == '') {
                        error = true;
                        userUpdate = false;
                    }

                    /* validate sepa direct debit - no error if no blz is set @toDo: fix for international direct debits */
                    if ($(this).attr('id') == 'ratepay_debit_bankcode' && !$(":input#ratepay_debit_accountnumber").val().match(/^\d+$/)) {
                        error = false;
                        userUpdate = true;
                    }

                });

                /* dob validation */
                if ($('#ratepay_birthyear').val() != '' && $('#ratepay_birthmonth').val() != '' && $('#ratepay_birthday').val() != '') {
                    dob = new Date($('#ratepay_birthyear').val() + '-' + $('#ratepay_birthmonth').val() + '-' + $('#ratepay_birthday').val());

                    /* validate age */
                    if (getAge(dob) < 18 || getAge(dob) > 120) {
                        error = true;
                        userUpdate = false;
                        errorMessage = errorMessageValidAge;
                    }

                    requestParams += '&ratepay_dob=' + dob.yyyymmdd();
                } else {
                    error = true;
                    userUpdate = false;
                }

                /* phone number validation */
                if ($('#ratepay_phone').val() != '') {

                    var regex = /[0-9-()+]{3,20}/;
                    var phoneNumber = $('#ratepay_phone').val().replace(/ /g, '');

                    if ($('#ratepay_phone').val().length < 6 || ( phoneNumber.match(regex) != phoneNumber )) {
                        error = true;
                        userUpdate = false;
                        errorMessage = errorMessageValidPhone;
                    }
                } else {
                    error = true;
                    userUpdate = false;
                }
                
                if($('#ratepay_debit_accountnumber').length) { /* only do the check if bankdata form exists */
                    if ($('#ratepay_debit_accountnumber').val() == '' || $('#ratepay_debit_accountholder').val() == '') {
                        error = true;
                        userUpdate = false;
                        errorMessage = errorMessageValidBankData;
                    } else if (!$("#ratepay_debit_accountnumber").val().replace(/ /g,'').match(/^\d+$/)) {
                        if($('#ratepay_debit_accountnumber').val().replace(/ /g,'').length < 18) {
                            error = true;
                            userUpdate = false;
                            errorMessage = errorMessageValidBankData;
                        }

                    }
                }

                /* error handler */
                if (error) {

                    /** hide the modal window */
                    $('div.ratepay-overlay').hide();

                    $('#ratepay_error').text(errorMessage);
                    $('#ratepay_error').parent().removeClass('is--hidden');
                    $('html, body').animate({
                        scrollTop: $('#ratepay_error').offset().top - 100
                    }, 1000);
                    return false;

                } else {
                    $('#ratepay_error').parent().hide();
                }

                /* update user */
                if (userUpdate) {
                    $.ajax({
                        type: 'POST',
                        async: false,
                        url: ratepayUrl,
                        data: requestParams
                    }).done(function (msg) {
                        if (msg == 'OK') {
                            console.log(messageConsoleLogOk);
                        } else {
                            console.log(messageConsoleLogError + msg);
                        }
                    });
                }

            }


        },

        /** Destroys the plugin */
        destroy: function () {
            this._destroy();
        }
    });

    $('body').RpayRatePay();
})(jQuery);
