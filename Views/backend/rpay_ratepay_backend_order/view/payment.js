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

        var changePaymentTypeHandler = function(combobox, newValue, oldValue) {
            if (newValue === '') return false;
            var paymentRecord = combobox.store.findRecord('id', newValue),
                name = paymentRecord.get('name');

            //not a ratepay order
            if (name.indexOf('rpay') !== 0) {
                return true;
            } else {
                if (me.customerId === -1) {
                    Shopware.Notification.createGrowlMessage('', me.snippetsLocal.loadCustomerFirst);
                    combobox.setValue('');
                    return false;
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

            //rpayratepayrate0
            //rpayratepaydebit
            //rpayratepayrate
            //rpayratepayinvoice
        };

        me.paymentComboBox.on('change', changePaymentTypeHandler);
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