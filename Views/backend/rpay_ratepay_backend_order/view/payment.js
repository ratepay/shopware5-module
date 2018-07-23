//
//{block name="backend/ratepay_backend_order/view/payment"}
//
Ext.define('Shopware.apps.RatepayBackendOrder.view.payment', {
    override: 'Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Payment',
    snippetsLocal: {
        loadCustomerFirst: '{s namespace="RatePAY/backend/backend_orders" name="load_customer_first"}Laden Sie bitte den Kunden zuerst.{/s}'
    },
    initComponent : function() {
        var me = this;

        me.callParent(arguments);

        me.bankDataContainer = me.createBankDataContainer();
        me.add(me.bankDataContainer);
        me.bankDataContainer.setVisible(false);

        me.calculatorStore = me.createCalculatorStore();
        me.calculatorContainer = me.createCalculatorContainer();
        me.add(me.calculatorContainer);
        me.calculatorContainer.setVisible(false);

        var changePaymentTypeHandler = function(combobox, newValue, oldValue) {
            if (newValue === '') return false;
            var  name = combobox.store.findRecord('id', newValue).get('name');
            me.paymentMeansName = name;

            me.bankDataContainer.setVisible(false);
            me.calculatorContainer.setVisible(false);

            //not a ratepay order
            if (name.indexOf('rpay') !== 0) {
                return true;
            } else {

                if (me.customerId === -1) {
                    Shopware.Notification.createGrowlMessage('', me.snippetsLocal.loadCustomerFirst);
                    combobox.setValue('');
                    return false;
                }
                if(name === 'rpayratepayrate0' || name === 'rpayratepayrate') {
                    var backendOrder = me.getBackendOrder();

                    if (backendOrder === null) {
                        Shopware.Notification.createGrowlMessage('','Please set shipping costs and items first. Ct ' + ct);//me.snippetsLocal.orderNotYetModelled);
                        combobox.setValue('');
                        return;
                    }

                    //now check total basket amount
                    var totalAmount = me.getTotalAmount()
                    var shippingCosts = me.getShippingCosts();

                    if (totalAmount < 0.01  || (totalAmount - shippingCosts) < 0.01) {
                        Shopware.Notification.createGrowlMessage('','Please put something in the shopping cart.');//me.snippetsLocal.orderNotYetModelled);
                        combobox.setValue('');
                        return;
                    }

                    me.calculatorContainer.setVisible(true);

                    me.requestInstallmentCalculator(
                        me.getShopId(),
                        me.getBillingAddressId(),
                        name,
                        totalAmount
                    );
                    //load ratenrechner
                } else if(name === 'rpayratepaydebit') {
                    //load bank bank data fields
                    me.bankDataContainer.setVisible(true);
                }

                //check for birthday and telephone number
                /*Ext.Ajax.request({
                    url: '{url controller="RpayRatepayBackendOrder" action="prevalidate"}',
                    params: {
                        customerId: me.customerId,
                        totalCost: me.getTotalCost(),
                        billingId: me.getBillingId(),
                        shippingId: me.getShippingId(),
                        paymentTypeName: name
                    },
                    success: function(response) {
                        var responseObj = Ext.decode(response.responseText);

                        if(responseObj.success === false) {
                            responseObj.messages.forEach(function(message) {
                                Shopware.Notification.createGrowlMessage('', message);
                            });
                            combobox.setValue('');
                        }
                    }
                });*/
            }

        };


        console.log('APP trying to bind to select billing address event');
        me.subApplication.app.on('selectBillingAddress', function() {
            alert('select billing address');
        });

        me.paymentComboBox.on('change', changePaymentTypeHandler);
    },
    iban: null,
    accountNumber: null,
    bankCode: null,
    requestInstallmentCalculator: function(shopId, billingAddressId, paymentTypeName, totalAmount) {
        var me = this;
        Ext.Ajax.request({
            url: '{url controller="RpayRatepayBackendOrder" action="getInstallmentInfo"}',
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
    handleBankDataBlur: function() {
        var me = this;

        //very minimalistic validation
        if(me.iban || (me.bankCode && me.iban)) {
            Ext.Ajax.request({
                url: '{url controller="RpayRatepayBackendOrder" action="getInstallmentInfo"}',
                params: {
                    iban: me.iban,
                    accountNumber: me.accountNumber,
                    bankCode: me.bankCode
                },
                success: function (response) {
                    var responseObj = Ext.decode(response.responseText);

                    if (responseObj.success === false) {
                        responseObj.messages.forEach(function (message) {
                            Shopware.Notification.createGrowlMessage('', message);
                        });
                    } else {
                        Shopware.Notification.createGrowlMessage('', 'Ratepay Bankdaten aktualisiert.');
                    }
                }
            });
        }
    },
    handleCalculatorInput: function(value, type) {
        var me = this;

        Ext.Ajax.request({
            url: '{url controller="RpayRatepayBackendOrder" action="getInstallmentPlan"}',
            params: {
                shopId: me.getShopId(),
                billingId: me.getBillingAddressId(),
                paymentMeansName: me.paymentMeansName,
                totalAmount: me.getTotalAmount(),
                type: type,
                value: value,
            },
            success: function (response) {
                var responseObj = Ext.decode(response.responseText);

                if (responseObj.success === false) {
                    responseObj.messages.forEach(function (message) {
                        Shopware.Notification.createGrowlMessage('', message);
                    });
                } else {
                    Shopware.Notification.createGrowlMessage('', 'Plan Successfully loaded');
                    console.log('PLAN: ' + JSON.stringify(responseObj.plan));
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
    createCalculatorContainer: function() {
        var me = this;

        var combobox = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Term',
            name: 'calculatorSelect',
            store: me.calculatorStore,
            queryMode: 'local',
            displayField: 'display',
            valueField: 'value',
            listeners: {
                change: function(combo, newValue, oldValue) {
                    me.handleCalculatorInput.call(me, newValue, "time");
                }
            }
        });

        var moneyTxtBox = Ext.create('Ext.form.TextField', {
            name: 'moneyTxtBox',
            width: 230,
            fieldLabel: 'Geld',
            maxLengthText: 255,
            listeners: {
                blur: function (field) {
                    var newValue = field.getValue();
                    me.handleCalculatorInput.call(me, newValue, "rate");
                }
            }
        });

        return Ext.create('Ext.Container', {
            name: 'bankDataContainer',
            width: 255,
            height: 'auto',
            items: [
                combobox, moneyTxtBox
            ]
        });

    },
    createBankDataContainer: function() {
        var me = this;
        var iban = Ext.create('Ext.form.TextField', {
            name: 'ibanTxtBox',
            width: 230,
            fieldLabel: 'IBAN',
            maxLengthText: 255,
            listeners: {
                blur: function (field) {
                    me.iban = field.getValue();
                    me.handleBankDataBlur();
                }
            }
        });

        var kontoNr = Ext.create('Ext.form.TextField', {
            name: 'ktoNrTxtBox',
            width: 230,
            fieldLabel: 'Kto Nr.',
            maxLengthText: 255,
            listeners: {
                blur: function (field) {
                    me.accountNumber = field.getValue();
                    me.handleBankDataBlur();
                }
            }
        });

        var blz = Ext.create('Ext.form.TextField', {
            name: 'blzTxtBox',
            width: 230,
            fieldLabel: 'BLZ',
            maxLengthText: 255,
            listeners: {
                blur: function (field) {
                    me.bankCode = field.getValue();
                    me.handleBankDataBlur();
                }
            }
        });

        return Ext.create('Ext.Container', {
            name: 'bankDataContainer',
            width: 255,
            height: 'auto',
            items: [
                iban, kontoNr, blz
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

        return me.subApplication.getStore('Customer')
            .getAt(0);
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
        var totalCostsModel = totalCostsStore.getAt(0);
        var totalAmount =  totalCostsModel.get('total');
        return totalAmount;
    },
    getShippingCosts: function() {
        var me = this;
        var totalCostsStore = me.subApplication.getStore('TotalCosts');
        if (totalCostsStore.getCount() === 0) {
            return null;
        }
        var totalCostsModel = totalCostsStore.getAt(0);
        var shippingCosts =  totalCostsModel.get('shippingCosts');
        return shippingCosts;
    }
});
//
//{/block}