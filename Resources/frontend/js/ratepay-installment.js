/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

;(function ($) {
    $.plugin('RatePayInstallmentCalculator', {

        defaults: {
            activeCls: 'js--is-active',
            calcRequestUrl: null,
            totalAmount: null,
            allowBankTransfer: null,
            allowDirectDebit: null,
            bankTransferPaymentFirstDay: null,
            directDebitPaymentFirstDay: null,

            selectorCalculator: '.rp-container-calculator',
            selectorRuntimeSelect: '#rp-btn-runtime',
            selectorFixAmountButton: '.rp-btn-rate',
            selectorFixAmountInput: '#rp-rate-value',
            selectorIbanInput: '#rp-iban-account-number',
            selectorPaymentSwitchDirectDebit: '#rp-switch-payment-type-direct-debit',
            selectorPaymentSwitchBankTransfer: '#rp-switch-payment-type-bank-transfer',
            selectorCalculationType: 'input[name="ratepay[installment][type]"]',
            selectorCalculationValue: 'input[name="ratepay[installment][value]"]',
            selectorPaymentTypeValue: 'input[name="ratepay[installment][paymentType]"]',
            selectorSepaForm: '.rp-sepa-form',
            selectorPlanResult: '#rpResultContainer'
        },
        paymentTypes: {
            bankTransfer: 'BANK-TRANSFER',
            directDebit: 'DIRECT-DEBIT'
        },
        hasBeenInit: false,

        init: function () {
            var me = this;

            me.applyDataAttributes();

            me.opts.allowBankTransfer = me.opts.allowBankTransfer === 1;
            me.opts.allowDirectDebit = me.opts.allowDirectDebit === 1;

            me._on(me.opts.selectorRuntimeSelect, 'change', $.proxy(me.selectRuntime, me));
            me._on(me.opts.selectorFixAmountButton, 'click', $.proxy(me.selectFixAmount, me));
            me._on(me.opts.selectorFixAmountInput, 'keyup', $.proxy(me.keyupFixedAmount, me));
            me._on(me.opts.selectorPaymentSwitchDirectDebit, 'click', $.proxy(function () {
                me.switchPaymentType(this.paymentTypes.directDebit);
            }, me));
            me._on(me.opts.selectorPaymentSwitchBankTransfer, 'click', $.proxy(function () {
                me.switchPaymentType(this.paymentTypes.bankTransfer);
            }, me));

            me.initCalculator();
            me.hasBeenInit = true;
        },

        initCalculator: function () {
            var me = this;

            me.initSepaFields();

            var $runTimeSelect = me.$el.find(me.opts.selectorRuntimeSelect);
            var $runTimeSelectAbleOptions = $runTimeSelect.children().filter(function () {
                return $(this).prop('value') !== ''
            });
            if ($runTimeSelectAbleOptions.length > 1) {
                me.$el.find(me.opts.selectorCalculator).show();
            }
            var $calculationType = me.$el.find(me.opts.selectorCalculationType);
            var $calculationValue = me.$el.find(me.opts.selectorCalculationValue);
            if ($calculationType.val().length && $calculationValue.val().length) {
                me.callInstallmentPlan(
                    $calculationType.val(),
                    $calculationValue.val()
                );
            } else {
                $runTimeSelect.val($runTimeSelectAbleOptions.first().val());
                $runTimeSelect.trigger('change');
            }

            StateManager.updatePlugin('select', 'swSelectboxReplacement');
        },

        initSepaFields: function () {
            var me = this;

            if (me.opts.allowDirectDebit) {
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

                me.callInstallmentPlan(
                    'time',
                    $select.val()
                );

                me.$el.find(me.opts.selectorFixAmountInput).val("");
            }
        },

        selectFixAmount: function (event) {
            var me = this;
            event.preventDefault();

            me.callInstallmentPlan(
                'rate',
                me.$el.find(me.opts.selectorFixAmountInput).val()
            );
        },

        keyupFixedAmount: function (event) {
            var me = this,
                value = me.$el.find(me.opts.selectorFixAmountInput).val();
            event.preventDefault();

            if (value.length === 0 || !jQuery.isNumeric(value)) {
                me.$el.find(me.opts.selectorFixAmountButton).prop('disabled', true);
            } else {
                me.$el.find(me.opts.selectorFixAmountButton).prop('disabled', false);
            }
        },

        switchPaymentType: function (paymentType) {
            var me = this,
                $sepaForm = me.$el.find(me.opts.selectorSepaForm),
                $fields = $sepaForm.find('input'),
                oldValue = $(me.opts.selectorPaymentTypeValue).val();

            if (paymentType === me.paymentTypes.bankTransfer) {
                $sepaForm.hide();
                $fields.prop('disabled', true).prop('required', false);

                me.$el.find(me.opts.selectorPaymentSwitchBankTransfer).hide();
                me.$el.find(me.opts.selectorPaymentSwitchDirectDebit).show();

                $(me.opts.selectorPaymentTypeValue).val(paymentType);
            } else if (paymentType === me.paymentTypes.directDebit) {
                $sepaForm.show();
                $fields.prop('disabled', false).prop('required', true);

                me.$el.find(me.opts.selectorPaymentSwitchDirectDebit).hide();
                me.$el.find(me.opts.selectorPaymentSwitchBankTransfer).show();

                $(me.opts.selectorPaymentTypeValue).val(paymentType);

                if (this.hasBeenInit === true && oldValue !== paymentType) {
                    me.callInstallmentPlan(
                        me.$el.find(me.opts.selectorCalculationType).val(),
                        me.$el.find(me.opts.selectorCalculationValue).val()
                    );
                }
            }
        },

        callInstallmentPlan: function (calcType, calcValue) {
            var me = this;

            jQuery.ajax({
                url: me.opts.calcRequestUrl,
                method: "GET",
                data: {
                    calculationAmount: me.opts.totalAmount,
                    calculationValue: calcValue,
                    calculationType: calcType,
                    paymentMethodId: $('[name=payment]:checked').val(),
                    paymentType: $(me.opts.selectorPaymentTypeValue).val()
                }
            }).done(function (result) {
                me.$el.find(me.opts.selectorPlanResult).html(result);
                me.$el.find(me.opts.selectorCalculationType).val(calcType);
                me.$el.find(me.opts.selectorCalculationValue).val(calcValue);
            }).fail(function () {
                alert("error");
            });
        },

    });

    StateManager.addPlugin('[data-ratepay-installment-calculator="true"]', 'RatePayInstallmentCalculator');
    $.subscribe('plugin/swShippingPayment/onInputChanged', function () {
        StateManager.addPlugin('[data-ratepay-installment-calculator="true"]', 'RatePayInstallmentCalculator');
    });
})(jQuery);

