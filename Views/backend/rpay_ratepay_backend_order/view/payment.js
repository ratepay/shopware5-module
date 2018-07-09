//
//{block name="backend/ratepay_backend_order/view/payment"}
//
Ext.define('Shopware.apps.RatepayBackendOrder.view.payment', {
    override: 'Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Payment',
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
        me.paymentComboBox.on('change', function() {
            alert('HELLO WORlD');
        });
    }
});
//
//{/block}