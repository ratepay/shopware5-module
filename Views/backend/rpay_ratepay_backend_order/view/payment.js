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
            if(name.indexOf('rpay') !== 0) {
                return true;
            } else {
                if(me.customerId === -1) {
                    Shopware.Notification.createGrowlMessage('', me.snippetsLocal.loadCustomerFirst);
                    combobox.setValue('');
                    return false;
                }

                //check for birthday and telephone number
                Ext.Ajax.request({
                    url: '{url controller="RpayRatepayBackendOrder" action="checkCustomerData"}',
                    params: {
                       customerId: me.customerId
                    },
                    success: function(response) {
                        var responseObj = Ext.decode(response.responseText);

                        if(responseObj.success === false) {
                            alert('Following order occurred ' + responseObj.messages[0]);
                        } else {
                            alert("everything ok");
                        }
                    }
                });
            }

            //rpayratepayrate0
            //rpayratepaydebit
            //rpayratepayrate
            //rpayratepayinvoice
        };

        me.paymentComboBox.on('change', changePaymentTypeHandler);
    }
});
//
//{/block}