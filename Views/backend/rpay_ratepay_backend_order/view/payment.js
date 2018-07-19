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

        var changePaymentTypeHandler = function(combobox, newValue, oldValue) {
            if (newValue === '') return false;
            var paymentRecord = combobox.store.findRecord('id', newValue),
                name = paymentRecord.get('name');

            console.log('Removing BankDataContainer');

            me.bankDataContainer.setVisible(false);
            //not a ratepay order
            if (name.indexOf('rpay') !== 0) {
                return true;
            } else {
                me.paymentDataView.setVisible(false);

                //because this view is created in another handler

                //rpayratepayrate0
                //rpayratepaydebit
                //rpayratepayrate
                //rpayratepayinvoice

                if(name === 'rpayratepayrate0' || name === 'rpayratepayrate') {
                     //load ratenrechner
                } else if(name === 'rpayratepaydebit') {
                    //load bank bank data fields
                    me.bankDataContainer.setVisible(true);
                }

                /*if (me.customerId === -1) {
                    Shopware.Notification.createGrowlMessage('', me.snippetsLocal.loadCustomerFirst);
                    combobox.setValue('');
                    return false;
                }*/

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


        me.paymentComboBox.on('change', changePaymentTypeHandler);
    },
    iban: null,
    accountNumber: null,
    bankCode: null,
    handleBankDataBlur: function() {
        var me = this;
        console.log('account ' + me.accountNumber + ' bankCode ' + me.bankCode + ' iban ' + me.iban);

        //very minimalistic validation
        if(me.iban || (me.bankCode && me.iban)) {

            Ext.Ajax.request({
                url: '{url controller="RpayRatepayBackendOrder" action="setExtendedData"}',
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
    createBankDataContainer : function() {
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
    getTotalCost: function() {
        var me = this;
        var totalCostsStore = me.subApplication.getStore("TotalCosts");
        var totalCostsModel = totalCostsStore.getAt(0);
        if (totalCostsModel == undefined) {
            return 0;
        } else {
            return totalCostsModel.get("total");
        }
    },
    getShippingId: function() {
        //this should work
        var orderModel = me.subApplication.getCreateBackendOrderModel();
    },
    getBillingId: function() {
        var me = this;
    }
});
//
//{/block}