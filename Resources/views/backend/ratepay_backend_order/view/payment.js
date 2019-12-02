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

    //override init
    initComponent : function() {
        var me = this;

        me.callParent(arguments);

        me.directDebitCheckbox = me.createDirectDebitCheckbox();
        me.add(me.directDebitCheckbox);
        me.directDebitCheckbox.setVisible(false);

        me.bankDataContainer = me.createBankDataContainer();
        me.add(me.bankDataContainer);
        me.bankDataContainer.setVisible(false);

        me.calculatorStore = me.createCalculatorStore();
        me.calculatorContainer = me.createCalculatorContainer();
        me.add(me.calculatorContainer);
        me.calculatorContainer.setVisible(false);

        //TODO: unbind this when the plugin is closed
        Ext.Ajax.on('requestcomplete', function(conn, req, opts) {
            var url = req.request.options.url;

            if(url ===  '{url controller="SwagBackendOrder" action="getCustomerPaymentData"}' &&
                me.paymentMeansName.indexOf('rpay') === 0) {
                // hide the customer payment data views which ratepay doesn't use
                setTimeout( function() {
                    if (me.noDataView instanceof Ext.view.View) {
                        me.remove(me.noDataView);
                    }
                    me.paymentDataView.setVisible(false);
                }, 1);
                //return false;
            }
            return true;
        });

        me.subApplication.app.on('selectBillingAddress', function() {
            alert(me.snippetsLocal.chooseBillingAddress);
        });

        var changePaymentTypeHandler = function(combobox, newValue, oldValue) {
           me.handleChangePaymentType.call(me, combobox, newValue);
        };

        me.paymentComboBox.on('change', changePaymentTypeHandler);

    },
    fail: function(combobox, message) {
        Shopware.Notification.createGrowlMessage('', message);
        combobox.setValue('');
    },
    handleChangePaymentType: function(combobox, newValue) {
        var me = this;

        if (newValue === '') {
            return;
        }

        var  name = combobox.store.findRecord('id', newValue).get('name');
        me.paymentMeansName = name;

        me.bankDataContainer.setVisible(false);
        me.calculatorContainer.setVisible(false);
        me.directDebitCheckbox.setVisible(false);

        me.remove('calculationResultView');

        //not a ratepay order
        if (name.indexOf('rpay') !== 0) {
            return true;
        } else {

            if (me.customerId === -1) {
                me.fail(combobox, me.snippetsLocal.messages.chooseCustomerFirst);
                return;
            }

            // hide the customer payment data views which ratepay doesn't use
            if (me.noDataView instanceof Ext.view.View) {
                me.remove(me.noDataView);
            }

            me.paymentDataView.setVisible(false);

            if(name === 'rpayratepayrate0' || name === 'rpayratepayrate') {
                me.handleChangePaymentInstallment(name, combobox);
            } else if (name === 'rpayratepaydebit') {
                //load bank bank data fields
                me.bankDataContainer.setVisible(true);
            }
        }
    },
    handleChangePaymentInstallment: function(paymentMeansName, combobox) {
        var me = this;
        var backendOrder = me.getBackendOrder();

        if (backendOrder === null) {
            me.fail(combobox, me.snippetsLocal.messages.setupCartFirst);
            return;
        }

        //now check total basket amount
        var totalAmount = me.getTotalAmount();
        var shippingCosts = me.getShippingCosts();

        if (totalAmount < 0.01  || (totalAmount - shippingCosts) < 0.01) {
            me.fail(combobox, me.snippetsLocal.messages.setupCartFirst);
            return;
        }

        var billingId = me.getBillingAddressId();

        if (billingId === null) {
            me.fail(combobox, me.snippetsLocal.messages.chooseBillingAddress);
            return;
        }

        //get payment options, direct debit or invoice

        Ext.Ajax.request({
            url: '{url controller="RatepayBackendOrder" action="getInstallmentPaymentOptions"}',
            params: {
                shopId: me.getShopId(),
                billingId: billingId,
                paymentMeansName: paymentMeansName,
            },
            success: function (response) {
                var responseObj = Ext.decode(response.responseText);
                var options = responseObj.options;
                var TRANSFER = 28;

                if (options.length === 1) {
                    me.installmentPaymentType = options[0];
                    me.directDebitCheckbox.setVisible(false);
                } else {
                    me.installmentPaymentType = TRANSFER;
                    //show switch for bank data
                    me.directDebitCheckbox.setValue(false);
                    me.directDebitCheckbox.setVisible(true);
                }

                if(paymentMeansName === 'rpayratepayrate0') {
                    //load directly fo 0%, since there are no variable terms
                    me.handleCalculatorInput();
                } else {
                    me.calculatorContainer.setVisible(true);

                    me.requestInstallmentCalculator(
                        me.getShopId(),
                        billingId,
                        paymentMeansName,
                        totalAmount,
                        options
                    );
                }
            }
        });
    },
    requestInstallmentCalculator: function(shopId, billingAddressId, paymentTypeName, totalAmount) {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="RatepayBackendOrder" action="getInstallmentInfo"}',
            params: {
                shopId: shopId,
                billingId: billingAddressId,
                paymentTypeName: paymentTypeName,
                totalAmount: totalAmount
            },
            success: function(response) {
                var responseObj = Ext.decode(response.responseText);

                if (responseObj.success === false) {
                    responseObj.messages.forEach(function (message) {
                        Shopware.Notification.createGrowlMessage('', message);
                    });
                } else {
                    var termInfo = responseObj.termInfo;
                    var months = termInfo.rp_allowedMonths;
                    me.calculatorStore.loadData(
                        months.map(
                            function(m) {
                                return {
                                    display: m,
                                    value: m
                                };
                            }
                        )
                    );
                }
            }
        });
    },
    handleCalculatorInput: function(value = null, type = null) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="RatepayBackendOrder" action="getInstallmentPlan"}',
            params: {
                shopId: me.getShopId(),
                billingId: me.getBillingAddressId(),
                paymentMeansName: me.paymentMeansName,
                totalAmount: me.getTotalAmount(),
                type: type,
                value: value,
                paymentSubtype: me.installmentPaymentType
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
                    me.doLayout();
                }
            }
        });
    },
    createCalculatorStore: function() {
        return Ext.create('Ext.data.Store', {
            fields: ['display', 'value'],
            data : []
        });
    },
    createDirectDebitCheckbox: function() {
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
                    var DIRECT_DEBIT = 2,
                        TRANSFER = 28;
                    me.bankDataContainer.setVisible(value);
                    me.installmentPaymentType = value ? DIRECT_DEBIT : TRANSFER;

                    Ext.Ajax.request({
                        url: '{url controller="RatepayBackendOrder" action="updatePaymentSubtype"}',
                        params: {
                            paymentSubtype: me.installmentPaymentType,
                        },
                        success: function () {
                            //Shopware.Notification.createGrowlMessage('', 'Zahlart auf Server gesetzt');
                        }
                    });
                }
            }
        });
    },
    createCalculatorResultTemplate: function(data) {
        var me = this;
        return new Ext.XTemplate(
            '<table><tbody>' +
            '<tr><td>'+me.snippetsLocal.labels.basePrice+':</td><td style="text-align: right;" >' + data.amount.toFixed(2) + '</td></tr>' +
            '<tr><td>'+me.snippetsLocal.labels.interest+':</td><td style="text-align: right;" >' + data.interestAmount.toFixed(2) +  '<trspan></tr>' +
            '<tr><td>'+me.snippetsLocal.labels.total+':</td><td style="text-align: right;" >' + data.totalAmount.toFixed(2) + '</td></tr>' +
            '<tr><td>'+me.snippetsLocal.labels.numberOfInstallments+':</td><td style="text-align: right;" >' + data.numberOfRatesFull + '</td></tr>' +
            '<tr><td>'+me.snippetsLocal.labels.standardPayment+':</td><td style="text-align: right;" >'+ data.rate.toFixed(2) + '</td></tr>' +
            '<tr><td>'+me.snippetsLocal.labels.lastPayment+':</td><td style="text-align: right;" >' + data.lastRate.toFixed(2) + '</td></tr>' +
            '</tbody></table>'
        );
    },
    createCalculatorContainer: function() {
        var me = this,
        fieldTerm, fieldInstallmentAmount;

        return Ext.create('Ext.Container', {
            name: 'bankDataContainer',
            width: 255,
            height: 'auto',
            items: [
                fieldTerm = Ext.create('Ext.form.ComboBox', {
                    fieldLabel: me.snippetsLocal.labels.term,
                    name: 'calculatorSelect',
                    store: me.calculatorStore,
                    queryMode: 'local',
                    displayField: 'display',
                    valueField: 'value',
                    listeners: {
                        change: function(combo, newValue, oldValue) {
                            if(newValue !== null) {
                                fieldInstallmentAmount.setValue(null);
                                me.handleCalculatorInput.call(me, newValue, "time");
                            }
                        }
                    }
                }),
                fieldInstallmentAmount = Ext.create('Ext.form.TextField', {
                    name: 'moneyTxtBox',
                    width: 230,
                    fieldLabel: me.snippetsLocal.labels.installmentAmount,
                    maxLengthText: 255,
                    listeners: {
                        blur: function (field) {
                            fieldTerm.setValue(null);
                            me.handleCalculatorInput.call(me, field.getValue(), "rate");
                        }
                    }
                })
            ]
        });

    },
    createBankDataContainer: function() {
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
    getCustomerModel: function() {
        var me = this;
        return me.subApplication.getStore('Customer').getAt(0);
    },
    getCustomerId: function () {
        var me = this;
        return me.getCustomerModel().get('id');
    },
    getShopId: function() {
        var me = this;
        var customerModel = me.getCustomerModel();
        if (customerModel !== null) {
            return customerModel.get('shopId');
        }
        return null;
    },
    getBillingAddressId: function() {
        var me = this;
        var backendOrder = me.getBackendOrder();
        if (backendOrder == null) {
            return null;
        }
        return backendOrder.get('billingAddressId');
    },
    getTotalAmount: function() {
        var me = this;
        var totalCostsStore = me.subApplication.getStore('TotalCosts');
        if (totalCostsStore.getCount() === 0) {
            return null;
        }
        return totalCostsStore.getAt(0).get('total');
    },
    getShippingCosts: function() {
        var me = this;
        var totalCostsStore = me.subApplication.getStore('TotalCosts');
        if (totalCostsStore.getCount() === 0) {
            return null;
        }
        return  totalCostsStore.getAt(0).get('shippingCosts');
    }
});
//
//{/block}
