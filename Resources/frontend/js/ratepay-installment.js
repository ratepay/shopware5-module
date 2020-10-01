/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

;(function () {
    $.plugin('RatePayInstallmentCalculator', {

        defaults: {
            activeCls: 'js--is-active',
            calcRequestUrl: null
        },
        paymentTypes: {
            bankTransfer: 'BANK-TRANSFER',
            directDebit: 'DIRECT-DEBIT'
        },
        selectors: {
            calculatorSelector: '.rp-container-calculator',
            runTimeButtonsSelector: '#rp-btn-runtime',
            fixAmountButtonSelector: '.rp-btn-rate',
            fixAmountInputSelector: '#rp-rate-value',
            ibanInputSelector: '#rp-iban-account-number',
            paymentSwitchDirectDebitSelector: '#rp-switch-payment-type-direct-debit a',
            paymentSwitchBankTransferSelector: '#rp-switch-payment-type-bank-transfer a',
        },
        hasBeenInit: false,

        init: function () {
            var me = this;

            me.applyDataAttributes();
            if (me.opts.calcRequestUrl == null) {
                alert('calcRequestUrl is not set!');
                return;
            }

            me._on(me.selectors.runTimeButtonsSelector, 'change', $.proxy(me.selectRuntime, me));
            me._on(me.selectors.fixAmountButtonSelector, 'click', $.proxy(me.selectFixAmount, me));
            me._on(me.selectors.fixAmountInputSelector, 'keyup', $.proxy(me.keyupFixedAmount, me));
            me._on(me.selectors.ibanInputSelector, 'keyup', $.proxy(me.switchBankCodeInput, me));
            me._on(me.selectors.ibanInputSelector, 'change', $.proxy(me.switchBankCodeInput, me));
            me._on(me.selectors.paymentSwitchDirectDebitSelector, 'click', $.proxy(function () {
                me.switchPaymentType(this.paymentTypes.directDebit);
            }, me));
            me._on(me.selectors.paymentSwitchBankTransferSelector, 'click', $.proxy(function () {
                me.switchPaymentType(this.paymentTypes.bankTransfer);
            }, me));

            me.initCalculator();
            me.hasBeenInit = true;
        },

        initCalculator: function () {
            var me = this;

            me.initSepaFields();

            var $runTimeSelect = me.$el.find(me.selectors.runTimeButtonsSelector);
            var $runTimeSelectAbleOptions = $runTimeSelect.children().filter(function () {
                return $(this).prop('value') !== ''
            });
            if ($runTimeSelectAbleOptions.length > 1) {
                me.$el.find(me.selectors.calculatorSelector).show();
            }
            if (me.$el.find('#rp-calculation-type').val().length && me.$el.find('#rp-calculation-value').val().length) {
                me.callInstallmentPlan(me.$el.find('#rp-calculation-type').val(), me.$el.find('#rp-calculation-value').val());
            } else {
                $runTimeSelect.val($runTimeSelectAbleOptions.first().val());
                $runTimeSelect.trigger('change');
            }
        },

        initSepaFields: function () {
            var me = this;

            if (window.rpBankTransferAllowed === false || window.rpDirectDebitAllowed === false) {
                me.$el.find('.rp-payment-type-switch').remove();
            }
            if (window.rpDirectDebitAllowed === false) {
                me.$el.find('.rp-sepa-form').remove();
            }

            me.$el.find("#rp-payment-firstday").val(window.rpDirectDebitAllowed ? rpDirectDebitFirstday : rpBankTransferFirstday);
            if (window.rpDirectDebitAllowed) {
                me.switchPaymentType(this.paymentTypes.directDebit);
            } else {
                // if direct debit is not allowed 'BANK_TRANSFER' is set by default
                me.switchPaymentType(this.paymentTypes.bankTransfer);
            }
        },

        selectRuntime: function (event) {
            var me = this;
            event.preventDefault();
            var $select = jQuery(event.currentTarget);
            if ($select.val().length !== 0) {
                me.callInstallmentPlan('time', $select.val());
                me.$el.find('#rp-rate-value').val("");
            }
        },
        selectFixAmount: function (event) {
            var me = this;
            event.preventDefault();
            me.callInstallmentPlan('rate', me.$el.find('#rp-rate-value').val());
            // de-marking of buttons
            jQuery(me.selectors.runTimeButtonsSelector).removeClass('btn-info');
        },
        keyupFixedAmount: function (event) {
            var me = this,
                value = me.$el.find('#rp-rate-value').val();
            event.preventDefault();

            if (value.length === 0 || !jQuery.isNumeric(value)) {
                me.$el.find(me.selectors.fixAmountButtonSelector).prop('disabled', true);
            } else {
                me.$el.find(me.selectors.fixAmountButtonSelector).prop('disabled', false);
            }
        },

        switchBankCodeInput: function (event) {
            // show bank code if account number is entered
            var me = this,
                value = jQuery(event.currentTarget).val();
            if (!jQuery.isNumeric(value)) {
                me.$el.find('#rp-form-bank-code').hide();
                me.$el.find('#rp-bank-code').prop('disabled', true).val('');
            } else {
                me.$el.find('#rp-form-bank-code').show();
                me.$el.find('#rp-bank-code').prop('disabled', false);
            }
        },

        switchPaymentType: function (paymentType) {
            var me = this,
                $sepaForm = me.$el.find('.rp-sepa-form'),
                $fields = $sepaForm.find('input').not('#rp-iban-account-holder'),
                oldValue = me.$el.find("#rp-payment-type").val();

            if (paymentType === me.paymentTypes.bankTransfer) {
                $sepaForm.hide();
                $fields.prop('disabled', true).prop('required', false);
                me.$el.find('#rp-switch-payment-type-bank-transfer').hide();
                me.$el.find('#rp-switch-payment-type-direct-debit').show();
                me.$el.find("#rp-payment-type").val(paymentType);
                me.$el.find("#rp-payment-firstday").val(window.rpBankTransferFirstday);
            } else if (paymentType === me.paymentTypes.directDebit) {
                $sepaForm.show();
                $fields.prop('disabled', false).prop('required', true);
                //jQuery('#rp-sepa-agreement').hide();
                me.$el.find('#rp-switch-payment-type-direct-debit').hide();
                me.$el.find('#rp-switch-payment-type-bank-transfer').show();
                me.$el.find("#rp-payment-type").val(paymentType);
                me.$el.find("#rp-payment-firstday").val(window.rpDirectDebitFirstday);
            }

            me.$el.find(me.selectors.ibanInputSelector).trigger('change');

            if (this.hasBeenInit === true && oldValue !== paymentType) {
                // After changing payment type, re-call of installment plan because of changed firstday
                me.callInstallmentPlan(me.$el.find('#rp-calculation-type').val(), me.$el.find('#rp-calculation-value').val());
            }
        },

        callInstallmentPlan: function (calcType, calcValue) {
            var me = this;

            jQuery.ajax({
                url: me.opts.calcRequestUrl,
                method: "GET",
                data: {
                    calculationAmount: me.$el.find('#rp-calculation-amount').val(),
                    calculationValue: calcValue,
                    calculationType: calcType,
                    paymentFirstday: me.$el.find("#rp-payment-firstday").val(),
                    paymentMethodId: $('[name=payment]:checked').val()
                }
            })
                .done(function (result) {
                    // show filled calculation plan template
                    me.$el.find('#rpResultContainer').html(result);

                    me.$el.find('#rp-calculation-type').val(calcType);
                    me.$el.find('#rp-calculation-value').val(calcValue);
                })
                .fail(function () {
                    alert("error");
                });
        },

    });


    // show sepa agreement
    /*jQuery('#rp-show-sepa-agreement').click(function() {
        jQuery(this).hide();
        jQuery('#rp-sepa-agreement').show();
    });*/

    // show bank code if account number is entered


    StateManager.addPlugin('[data-ratepay-installment-calculator="true"]', 'RatePayInstallmentCalculator');
    $.subscribe('plugin/swShippingPayment/onInputChanged', function () {
        StateManager.addPlugin('[data-ratepay-installment-calculator="true"]', 'RatePayInstallmentCalculator');
    });
})();

