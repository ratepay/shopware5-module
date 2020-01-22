;
(function ($, undefined) {

    /** Plugin constructor */
    $.plugin('RpayRatePay', {
        init: function () {
            if (typeof ratepayPaymentMethodSelected == 'undefined' || typeof ratepayConstantsExists == 'undefined') {
                return;
            }

            var me = this;

            me.$checkoutButton = $('button[form=confirm--form]');
            me.registerEvents();

            jQuery('[data-rp-local-storage="true"]').each(function (i, e) {
                var $el = jQuery(e);
                var value = me.getLocalValue($el.prop('name'));
                if (value) {
                    $el.val(value);
                }
            });

            /* exception if user age is not valid*/
            if(ratepayAgeNotValid == true) {
                me.notify(errorMessageAgeNotValid);
            }

            /* exception if no rate is calculated */
            if(ratepayCalcRateError == true) {
                me.notify(errorMessageCalcRate);
            }

            if ($("#paymentFirstday").val() == me.getBankTransfer()) {
                var debitDetails = $("#debitDetails");
                $("#paywire").hide();
                $("#wicAGB").hide();
                debitDetails.remove();
            }

            if ($("#paymentFirstday").val() == me.getDirectDebit()) {
                $("#debitDetails").hide();
                $("#paywire").show();
                $("#wicAGB").show();
                $("#payrp").remove();
                $("#ratepay_agb").prop('checked', false);
                $("#rpAGB").remove();

            }

            if ($(":input#ratepay_debit_bankcode") && !$("#paymentFirstday").val()) {
                $("#paywire").remove();
                $("#wicAGB").remove();
            }

            if ($("#paymentFirstday").val() == me.getDirectDebit() && $("#firstdaySwitch").val() == 1) {
                $("#changeFirstday").show();
            }

            if ($(":input#ratepay_debit_bankcode").length) {
                /* Disable confirmation button if sepadirectdebit tac are not checked */
                var button = $('button[type=submit]');

                button.attr('disabled', 'disabled');
                button.css({ opacity: 0.5 });
                button.attr('title', errorMessageAcceptSepaAGB);

                if ($('#ratepay_agb').is(':checked')) {
                    button.removeAttr('disabled');
                    button.removeAttr('title');
                    button.css({ opacity: 1.0 });
                } else {
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
                }

                var blzInput       = $(":input#ratepay_debit_bankcode");
                var blzBlock       = $(".ratepay_debit_bankcode");
                var accNumberInput = $(":input#ratepay_debit_accountnumber");

                blzInput.prop('disabled', true);
                blzBlock.hide();

                 $(document).ready(function() {
                    if (accNumberInput.val().match(/^\d+$/)) {
                        blzInput.prop('disabled', false);
                        blzBlock.show();
                    }
                    else {
                        blzInput.prop('disabled', true);
                        blzBlock.hide();
                    }
                })

                accNumberInput.on('blur keyup change click', function () {
                    if ($(this).val().match(/^\d+$/)) {
                        blzInput.prop('disabled', false);
                        blzBlock.show();
                    }
                    else {
                        blzInput.prop('disabled', true);
                        blzBlock.hide();
                    }
                })
            }
        },

        getBankTransfer: function () {
            return 28;
        },

        getDirectDebit: function () {
            return 2;
        },

        /**
         * Helper method to register the needed events
         */
        registerEvents: function () {
            var me = this;
            me._on(me.$checkoutButton, 'click', $.proxy(me.onCheckoutButtonClick, me));

            jQuery(document).on('blur', '[data-rp-local-storage="true"]', function (event) {
                var $el = jQuery(event.currentTarget);
                me.setLocalValue($el.prop('name'), $el.val());
            });
        },

        getLocalValue: function (fieldName) {
            return window.localStorage.getItem('_rp_' + fieldName);
        },
        setLocalValue: function (fieldName, value) {
            window.localStorage.setItem('_rp_' + fieldName, value);
        },


        /**
         * This method is called when the user click on checkout button in registration page
         *
         * @param event
         */
        onCheckoutButtonClick: function (event) {
            var me = this;
            var $submitButton = $(event.currentTarget);
            var preLoaderPlugin = $submitButton.data('plugin_swPreloaderButton');


            var $form = $('#'+$submitButton.attr('form'));
            if (!$form.length || !$form[0].checkValidity()) {
                return;
            }

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


            if (!$('input#sAGB').length || $('input#sAGB').is(':checked')) {
                var requestParams = 'userid=' + userId;
                var dob = false;
                var userUpdate = true;
                var hasErrors = false;
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
                    if ($(this).val() === '' && $(this).prop('required')) {
                        hasErrors = true;
                        userUpdate = false;
                    }

                    /* validate sepa direct debit - no error if no blz is set @toDo: fix for international direct debits */
                    if ($(this).attr('id') == 'ratepay_debit_bankcode' && !$(":input#ratepay_debit_accountnumber").val().match(/^\d+$/)) {
                        hasErrors = false;
                        userUpdate = true;
                    }

                });

                /* dob validation */
                if ($('#ratepay_birthyear').length) { /* only do the check if dob form exists */
                    var year = $('#ratepay_birthyear').val().trim();
                    var month = $('#ratepay_birthmonth').val().trim();
                    var day = $('#ratepay_birthday').val().trim();

                    if (
                        year.match(/^(1|2)\d{3}$/) // year begins with 1 or 2 followed by three digits
                        && month.match(/^(0?[1-9]|1[0-2])$/) // month can start with 0 followed by a digit (1-9) or starts with 1 followed by 0, 1 or 2
                        && day.match(/^(0?[1-9]|(1|2)[0-9]|3[0-1])$/) // comparable to month logic but capable to match numbers from 1-31
                    ) {
                        dob = new Date($('#ratepay_birthyear').val() + '-' + $('#ratepay_birthmonth').val() + '-' + $('#ratepay_birthday').val());

                        /* validate age */
                        if (getAge(dob) < 18 || getAge(dob) > 120) {
                            hasErrors = true;
                            userUpdate = false;
                            errorMessage = errorMessageValidAge;
                        }

                        /* validate correctness */
                        if ((month.match(/^(0?[4]|0?[6]|0?[9]|11)$/)) && day == 31) {
                            hasErrors = true;
                            userUpdate = false;
                            errorMessage = errorMessageDobNotValid;
                        }

                        /* check for february 29th */
                        if (month == 2) {
                            var isleap = (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0));
                            if (day > 29 || (day == 29 && !isleap)) {
                                hasErrors = true;
                                userUpdate = false;
                                errorMessage = errorMessageDobNotValid;
                            }
                        }

                        requestParams += '&ratepay_dob=' + dob.yyyymmdd();
                    } else {
                        hasErrors = true;
                        userUpdate = false;
                        errorMessage = errorMessageDobNotValid;
                    }
                }

                /* check for address editor, only shopware >=5.2.0 */
                if($(".btn[data-address-editor]").length) {
                    if($(".btn[data-sessionkey='checkoutBillingAddressId,checkoutShippingAddressId']").length) {
                        requestParams += '&checkoutBillingAddressId=' + $(".btn[data-sessionkey='checkoutBillingAddressId,checkoutShippingAddressId']").attr("data-id");
                        differentAddress = false;
                    } else {
                        requestParams += '&checkoutBillingAddressId=' + $(".btn[data-sessionkey='checkoutBillingAddressId']").attr("data-id");
                        requestParams += '&checkoutShippingAddressId=' + $(".btn[data-sessionkey='checkoutShippingAddressId']").attr("data-id");
                        differentAddress = true;
                    }
                }

                /* only do the check if bankdata form exists */
                if($('#ratepay_debit_accountnumber').length) {
                    if ((!$("#paymentFirstday")) || $("#paymentFirstday").val() == 2) {
                        if ($('#ratepay_debit_accountnumber').val() == '' || $('#ratepay_debit_accountholder').val() == '') {
                            hasErrors = true;
                            userUpdate = false;
                            errorMessage = errorMessageValidBankData;
                        } else if (!$("#ratepay_debit_accountnumber").val().replace(/ /g, '').match(/^\d+$/)) {
                            if ($('#ratepay_debit_accountnumber').val().replace(/ /g, '').length < 18) {
                                hasErrors = true;
                                userUpdate = false;
                                errorMessage = errorMessageValidBankData;
                            }

                        }
                    }
                }

                if (hasErrors === false) {
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
                                me.notify('', true);
                            } else {
                                hasErrors = true;
                                errorMessage = msg;
                                console.log(messageConsoleLogError + msg);
                            }
                        });
                    }
                }

                if(hasErrors) {
                    $('div.ratepay-overlay').hide();
                    me.notify(errorMessage);
                    if (event.preventDefault) {
                        event.preventDefault();
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    event.returnValue = false;
                    if (preLoaderPlugin) {
                        setTimeout(function() {
                            preLoaderPlugin.reset();
                        }, 1000);
                    }
                    return false;
                }

            }

        },

        notify: function (message, isHidden) {
            var container = $('#ratepay_error');
            if (isHidden) {
                container.parent().hide();
                container.parent().addClass('is--hidden');
                return;
            }

            container.text(message);
            container.parent().removeClass('is--hidden');
            container.parent().show();
            $('html, body').animate({ scrollTop: container.offset().top - 100 }, 1000);
        },

        /** Destroys the plugin */
        destroy: function () {
            this._destroy();
        }
    });

    $('body').RpayRatePay();
})(jQuery);
