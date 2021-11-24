/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name="backend/ratepay"}
//{block name="backend/ratepay_backend_order/view/payment"}
//
Ext.define('Shopware.apps.RatepayBackendOrder.view.payment', {
    override: 'Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Payment',
    snippetsLocal: {
        directDebit: '{s name="DirectDebit"}{/s}',
        messages: {
            chooseCustomerFirst: '{s name="ErrorLoadCustomerFirst"}{/s}',
            setupCartFirst: '{s name="ErrorSetupCartFirst"}{/s}',
            chooseBillingAddress: '{s name="ErrorChooseBillingAddress"}{/s}',
            bankDataRefreshed: '{s name="BankDataHasBeenRefreshed"}{/s}',
            installmentPlanLoaded: '{s name="InstallmentPlanLoaded"}{/s}',
        },
        labels: {
            installmentAmount: '{s name="LabelInstallmentAmount"}{/s}',
            term: '{s name="LabelTerm"}{/s}',
            iban: '{s name="LabelIban"}{/s}',

            //calculator labels
            basePrice: '{s name="LabelBasePrice"}{/s}',
            interest: '{s name="LabelInterest"}{/s}',
            total: '{s name="LabelTotal"}{/s}',
            numberOfInstallments: '{s name="LabelNumberOfInstallments"}{/s}',
            standardPayment: '{s name="LabelStandardPayment"}{/s}',
            lastPayment: '{s name="LabelLastPayment"}{/s}'
        }
    },
    installmentMethods: ['rpayratepayrate0', 'rpayratepayrate'],
    paymentMeansName: null,
    installmentCalculationType: null,
    installmentFieldCalculationTimeValue: null,
    installmentFieldCalculationRateValue: null,

    initComponent: function () {
        var me = this;

        me.callParent(arguments);

        me.installmentPaymentTypeCheckbox = me.createDirectDebitCheckbox();
        me.add(me.installmentPaymentTypeCheckbox);
        me.installmentPaymentTypeCheckbox.setVisible(false);

        me.directDebitBankDataContainer = me.createBankDataContainer();
        me.add(me.directDebitBankDataContainer);
        me.directDebitBankDataContainer.setVisible(false);

        me.calculatorStore = me.createCalculatorStore();
        me.calculatorContainer = me.createCalculatorContainer();
        me.add(me.calculatorContainer);
        me.calculatorContainer.setVisible(false);

        me.subApplication.app.on('selectBillingAddress', function () {
            Shopware.Notification.createGrowlMessage('', me.snippetsLocal.chooseBillingAddress);
        });

        me.subApplication.getStore('TotalCosts').on('update', function () {
            if (me.installmentCalculationType && me.installmentMethods.includes(me.paymentMeansName)) {
                // we must recalculate the plan
                // - reload the calculator
                // - update the "time"-select, if the allowed-months does not contains the selected month
                // - reload the plan
                me.requestInstallmentCalculator.call(me).then(function (responseObject) {
                    if (me.installmentCalculationType === 'time' && me.installmentFieldCalculationTimeValue.getValue() !== null) {
                        if (!responseObject.termInfo.rp_allowedMonths.includes(me.installmentFieldCalculationTimeValue.getValue())) {
                            me.installmentFieldCalculationTimeValue.setValue(responseObject.termInfo.rp_allowedMonths[0]);
                        }
                    }
                    me.updateInstallmentPlan.call(me);
                });
            }
        });

        me.paymentComboBox.on('change', function (combobox, newValue, oldValue) {
            me.handleChangePaymentMethod.call(me, newValue);
        });
    },

    add: function (element) {
        var me = this;

        if (element === me.noDataView && me.paymentMeansName.indexOf('rpay') === 0) {
            // prevent adding the `noDataView` element, if a ratepay method has been selected
            me.noDataView = null;
        } else {
            me.callParent(arguments);
        }
    },

    fail: function (combobox, message) {
        Shopware.Notification.createGrowlMessage('', message);
        combobox.setValue('');
    },

    handleChangePaymentMethod: function (newPaymentMethod) {
        var me = this;

        if (newPaymentMethod === '') {
            return;
        }

        me.paymentMeansName = me.paymentComboBox.store.findRecord('id', newPaymentMethod).get('name');

        me.directDebitBankDataContainer.setVisible(false);
        me.calculatorContainer.setVisible(false);
        me.installmentPaymentTypeCheckbox.setVisible(false);
        me.remove('calculationResultView');

        //not a ratepay order
        if (me.paymentMeansName.indexOf('rpay') !== 0) {
            return true;
        } else {

            if (me.customerId === -1) {
                me.fail(me.paymentComboBox, me.snippetsLocal.messages.chooseCustomerFirst);
                return;
            }

            // hide the customer payment data views which ratepay doesn't use
            if (me.noDataView instanceof Ext.view.View) {
                me.remove(me.noDataView);
            }

            me.paymentDataView.setVisible(false);

            if (me.installmentMethods.includes(me.paymentMeansName)) {
                me.handleChangePaymentInstallment(me.paymentMeansName);
            } else if (me.paymentMeansName === 'rpayratepaydebit') {
                //load bank bank data fields
                me.directDebitBankDataContainer.setVisible(true);
            }
        }
    },

    handleChangePaymentInstallment: function () {
        var me = this;
        var backendOrder = me.getBackendOrder();

        if (backendOrder === null) {
            me.fail(me.paymentComboBox, me.snippetsLocal.messages.setupCartFirst);
            return;
        }

        //now check total basket amount
        var totalAmount = me.getTotalAmount();
        var shippingCosts = me.getShippingCosts();

        if (totalAmount < 0.01 || (totalAmount - shippingCosts) < 0.01) {
            me.fail(me.paymentComboBox, me.snippetsLocal.messages.setupCartFirst);
            return;
        }

        var billingId = me.getBillingAddressId();

        if (billingId === null) {
            me.fail(me.paymentComboBox, me.snippetsLocal.messages.chooseBillingAddress);
            return;
        }

        me.requestInstallmentCalculator();
    },

    requestInstallmentCalculator: function () {
        var me = this;

        me.installmentPaymentTypeCheckbox.setVisible(false);
        me.directDebitBankDataContainer.setVisible(false);
        me.calculatorContainer.setVisible(false);

        return new Promise(function (resolve, reject) {
            Ext.Ajax.request({
                url: '{url controller="RatepayBackendOrder" action="getInstallmentInfo"}',
                params: {
                    shopId: me.getShopId(),
                    billingAddressId: me.getBillingAddressId(),
                    shippingAddressId: me.getShippingAddressId(),
                    currencyId: me.getCurrencyId(),
                    paymentMeansName: me.paymentMeansName,
                    totalAmount: me.getTotalAmount()
                },
                success: function (response) {
                    var responseObj = Ext.decode(response.responseText);

                    if (responseObj.success === false) {
                        responseObj.messages.forEach(function (message) {
                            Shopware.Notification.createGrowlMessage('', message);
                        });

                        me.calculatorContainer.setVisible(false);
                        reject(responseObj);
                    } else {
                        var termInfo = responseObj.termInfo;
                        var months = termInfo.rp_allowedMonths;
                        me.calculatorStore.loadData(
                            months.map(
                                function (m) {
                                    return {
                                        display: m,
                                        value: m
                                    };
                                }
                            )
                        );
                        me.calculatorContainer.setVisible(true);
                        resolve(responseObj);
                    }
                }
            });
        });
    },

    updateInstallmentPlan: function () {
        var me = this, calculationValue = null;

        if (!me.installmentCalculationType) {
            // prevent loading plan, if the customer has not entered some values
            return;
        } else if (me.installmentCalculationType === 'rate') {
            calculationValue = me.installmentFieldCalculationRateValue.getValue();
        } else if (me.installmentCalculationType === 'time') {
            calculationValue = me.installmentFieldCalculationTimeValue.getValue();
        } else {
            Shopware.Notification.createGrowlMessage('', me.snippetsLocal.messages.installmentPlanLoaded);
            console.err('invalid calculation type: ' + me.installmentCalculationType);
            return;
        }

        Ext.Ajax.request({
            url: '{url controller="RatepayBackendOrder" action="getInstallmentPlan"}',
            params: {
                shopId: me.getShopId(),
                billingAddressId: me.getBillingAddressId(),
                shippingAddressId: me.getShippingAddressId(),
                currencyId: me.getCurrencyId(),
                paymentMeansName: me.paymentMeansName,
                totalAmount: me.getTotalAmount(),
                type: me.installmentCalculationType,
                value: calculationValue,
                paymentType: me.installmentPaymentType
            },
            success: function (response) {
                var responseObj = Ext.decode(response.responseText);

                me.remove('calculationResultView');
                if (responseObj.success === false) {
                    responseObj.messages.forEach(function (message) {
                        Shopware.Notification.createGrowlMessage('', message);
                    });
                } else {
                    Shopware.Notification.createGrowlMessage('', me.snippetsLocal.messages.installmentPlanLoaded);

                    me.calculationResultView = Ext.create('Ext.view.View', {
                        id: 'calculationResultView',
                        name: 'calculationResultView',
                        tpl: me.createCalculatorResultTemplate(responseObj.plan)
                    });

                    me.add(me.calculationResultView);

                    // show payment-switch checkbox if there is more than one payment type
                    me.installmentPaymentTypeCheckbox.setVisible(responseObj.paymentTypes.length > 1);

                    if (!responseObj.paymentTypes.includes('DIRECT-DEBIT')) {
                        me.directDebitBankDataContainer.setVisible(false);
                        me.installmentPaymentTypeCheckbox.setVisible(false);
                        me.installmentPaymentTypeCheckbox.setValue(false);
                    } else if ((me.installmentPaymentType === null || responseObj.defaults.paymentType === 'DIRECT-DEBIT') ||
                        me.installmentPaymentType === 'DIRECT-DEBIT'
                    ) {
                        me.installmentPaymentTypeCheckbox.setValue(true);
                        me.directDebitBankDataContainer.setVisible(true);
                    }

                    me.doLayout();
                }
            }
        });
    },

    createCalculatorStore: function () {
        return Ext.create('Ext.data.Store', {
            fields: ['display', 'value'],
            data: []
        });
    },

    createDirectDebitCheckbox: function () {
        var me = this;
        return Ext.create('Ext.form.field.Checkbox', {
            boxLabel: me.snippetsLocal.directDebit,
            name: 'directDebitCheckbox',
            id: 'directDebitCheckbox',
            uncheckedValue: false,
            checked: false,
            height: 35,
            listeners: {
                change: function (field, value) {
                    me.directDebitBankDataContainer.setVisible(value);
                    me.installmentPaymentType = value ? 'DIRECT-DEBIT' : 'BANK-TRANSFER';

                    Ext.Ajax.request({
                        url: '{url controller="RatepayBackendOrder" action="updatePaymentSubtype"}',
                        params: {
                            paymentType: me.installmentPaymentType,
                        }
                    });
                }
            }
        });
    },

    createCalculatorResultTemplate: function (data) {
        var me = this;
        return new Ext.XTemplate(
            '<table><tbody>' +
            '<tr><td>' + me.snippetsLocal.labels.basePrice + ':</td><td style="text-align: right;" >' + data.amount.toFixed(2) + '</td></tr>' +
            '<tr><td>' + me.snippetsLocal.labels.interest + ':</td><td style="text-align: right;" >' + data.interestAmount.toFixed(2) + '<trspan></tr>' +
            '<tr><td>' + me.snippetsLocal.labels.total + ':</td><td style="text-align: right;" >' + data.totalAmount.toFixed(2) + '</td></tr>' +
            '<tr><td>' + me.snippetsLocal.labels.numberOfInstallments + ':</td><td style="text-align: right;" >' + data.numberOfRatesFull + '</td></tr>' +
            '<tr><td>' + me.snippetsLocal.labels.standardPayment + ':</td><td style="text-align: right;" >' + data.rate.toFixed(2) + '</td></tr>' +
            '<tr><td>' + me.snippetsLocal.labels.lastPayment + ':</td><td style="text-align: right;" >' + data.lastRate.toFixed(2) + '</td></tr>' +
            '</tbody></table>'
        );
    },

    createCalculatorContainer: function () {
        var me = this;

        return Ext.create('Ext.Container', {
            name: 'bankDataContainer',
            width: 255,
            height: 'auto',
            items: [
                me.installmentFieldCalculationTimeValue = Ext.create('Ext.form.ComboBox', {
                    fieldLabel: me.snippetsLocal.labels.term,
                    name: 'calculatorSelect',
                    store: me.calculatorStore,
                    queryMode: 'local',
                    displayField: 'display',
                    valueField: 'value',
                    listeners: {
                        change: function (combo, newValue, oldValue) {
                            if (newValue !== null) {
                                me.installmentCalculationType = "time";
                                me.installmentFieldCalculationRateValue.setValue(null);
                                me.updateInstallmentPlan.call(me);
                            }
                        }
                    }
                }),
                me.installmentFieldCalculationRateValue = Ext.create('Ext.form.TextField', {
                    name: 'moneyTxtBox',
                    width: 230,
                    fieldLabel: me.snippetsLocal.labels.installmentAmount,
                    maxLengthText: 255,
                    listeners: {
                        blur: function (field) {
                            if (field.getValue().length > 0) {
                                me.installmentCalculationType = "rate";
                                me.installmentFieldCalculationTimeValue.setValue(null);
                                me.updateInstallmentPlan.call(me);
                            }
                        }
                    }
                })
            ]
        });
    },

    createBankDataContainer: function () {
        var me = this;

        return Ext.create('Ext.Container', {
            name: 'bankDataContainer',
            width: 255,
            height: 'auto',
            items: [
                Ext.create('Ext.form.TextField', {
                    name: 'ibanTxtBox',
                    width: 230,
                    fieldLabel: me.snippetsLocal.labels.iban,
                    maxLengthText: 255,
                    listeners: {
                        blur: function (field) {
                            var customerId = me.getCustomerId();
                            if (!customerId) {
                                Shopware.Notification.createGrowlMessage('', me.snippetsLocal.messages.chooseCustomerFirst);
                                return;
                            }

                            // validate and save the iban number
                            Ext.Ajax.request({
                                url: '{url controller="RatepayBackendOrder" action="setBankData"}',
                                params: {
                                    customerId: customerId,
                                    iban: field.getValue()
                                },
                                success: function (response) {
                                    var responseObj = Ext.decode(response.responseText);

                                    if (responseObj.success === false) {
                                        responseObj.messages.forEach(function (message) {
                                            Shopware.Notification.createGrowlMessage('', message);
                                        });
                                    } else {
                                        Shopware.Notification.createGrowlMessage('', me.snippetsLocal.messages.bankDataRefreshed);
                                    }
                                }
                            });
                        }
                    }
                })
            ]
        });
    },

    getBackendOrder: function () {
        var me = this;
        var createBackendOrderStore = me.subApplication.getStore('CreateBackendOrder');
        var ct = createBackendOrderStore.getCount();
        if (ct === 0) {
            return null;
        }
        return createBackendOrderStore.getAt(ct - 1);
    },

    getCustomerModel: function () {
        var me = this;
        return me.subApplication.getStore('Customer').getAt(0);
    },

    getCustomerId: function () {
        var me = this;
        return me.getCustomerModel().get('id');
    },

    getShopId: function () {
        var me = this;
        var customerModel = me.getCustomerModel();
        if (customerModel !== null) {
            return customerModel.get('shopId');
        }
        return null;
    },

    getCurrencyId: function () {
        var me = this;
        var backendOrder = me.getBackendOrder();
        if (backendOrder == null) {
            return null;
        }
        return backendOrder.get('currencyId');
    },

    getBillingAddressId: function () {
        var me = this;
        var backendOrder = me.getBackendOrder();
        if (backendOrder == null) {
            return null;
        }
        return backendOrder.get('billingAddressId');
    },

    getShippingAddressId: function () {
        var me = this;
        var backendOrder = me.getBackendOrder();
        if (backendOrder == null) {
            return null;
        }
        return backendOrder.get('shippingAddressId') ? backendOrder.get('shippingAddressId') : me.getBillingAddressId();
    },

    getTotalAmount: function () {
        var me = this;
        var totalCostsStore = me.subApplication.getStore('TotalCosts');
        if (totalCostsStore.getCount() === 0) {
            return null;
        }
        return totalCostsStore.getAt(0).get('total');
    },

    getShippingCosts: function () {
        var me = this;
        var totalCostsStore = me.subApplication.getStore('TotalCosts');
        if (totalCostsStore.getCount() === 0) {
            return null;
        }
        return totalCostsStore.getAt(0).get('shippingCosts');
    }
});
//
//{/block}
