/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

////{namespace name=backend/order/main}
//{block name="backend/order/view/detail/articlemanagement/ratepaydelivery"}
Ext.define('Shopware.apps.RatepayOrder.view.detail.positionTabs.Articles', {

    /**
     * Define that the additional information is an Ext.panel.Panel extension
     * @string
     */
    extend: 'Ext.grid.Panel',
    autoScroll: true,
    layout: 'fit',
    plugins: Ext.create('Ext.grid.plugin.CellEditing', {
        clicksToEdit: 1
    }),
    initComponent: function () {
        var me = this;
        var positionStore = Ext.create('Shopware.apps.RatepayOrder.store.Position');
        var id = this.record.get('id');

        me.store = positionStore.load({
            params: {
                'orderId': id
            }
        });
        me.columns = {
            items: me.getColumns(),
            defaults: {
                flex: 1
            }
        };
        me.dockedItems = [
            {
                xtype: 'toolbar',
                dock: 'top',
                items: me.getToolbar()
            }
        ];

        me.callParent(arguments);
    },

    /**
     * Creates the grid columns
     *
     * @return [array] grid columns
     */
    getColumns: function () {
        return [
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/quantity"}{/s}',
                dataIndex: 'quantityDeliver',
                editor: {
                    xtype: 'numberfield',
                    hideTrigger: false,
                    allowBlank: false,
                    allowDecimals: false,
                    minValue: 0
                }
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/name"}{/s}',
                dataIndex: 'name'
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/number"}{/s}',
                dataIndex: 'articleordernumber'
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/price"}{/s}',
                dataIndex: 'price',
                renderer: Ext.util.Format.numberRenderer('0.000')
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/ordered"}{/s}',
                dataIndex: 'quantity'
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/delivered"}{/s}',
                dataIndex: 'delivered'
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/canceled"}{/s}',
                dataIndex: 'cancelled'
            },
            {
                header: '{s namespace="backend/ratepay/order_management" name="column/returned"}{/s}',
                dataIndex: 'returned'
            },
        ];
    },

    getToolbar: function () {
        var me = this;
        var id = me.record.get('id');
        return [
            {
                text: '{s namespace="backend/ratepay/order_management" name="column/reset"}{/s}',
                handler: function () {
                    var id = me.record.get('id');
                    var positionStore = Ext.create('Shopware.apps.RatepayOrder.store.Position');
                    me.store = positionStore.load({
                        params: {
                            'orderId': id,
                            'setToZero': true
                        }
                    });

                    me.reconfigure(me.store);
                }
            },
            /*{
                iconCls: 'sprite-inbox--plus',
                text: '{s namespace="backend/ratepay" name=addarticle}Artikel hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Shopware.apps.RpayRatepayOrderdetail.view.detail.ratepayadditemwindow', {
                        parent: me,
                        record: me.record
                    }).show();
                }
            },*/
            {
                iconCls: 'sprite-plus-circle-frame',
                text: '{s namespace="backend/ratepay/order_management" name="action/addCredit"}{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                        title: '{s namespace="backend/ratepay/order_management" name="action/addCredit"}{/s}',
                        width: 300,
                        height: 150,
                        id: 'creditWindow',
                        resizable: false,
                        layout: {
                            type: 'vbox',
                            align : 'stretch',
                            pack  : 'start',
                        },
                        items: [
                            {
                                xtype: 'textfield',
                                id: 'creditLabel',
                                fieldLabel: '{s namespace="backend/ratepay" name="label"}{/s}',
                                allowBlank: false
                            },
                            {
                                xtype: 'numberfield',
                                id: 'creditAmount',
                                fieldLabel: '{s namespace="backend/ratepay" name="amount"}{/s}',
                                helpText: '{s namespace="backend/ratepay" name="precisionNote"}{/s}',
                                allowBlank: false,
                                allowDecimals: true,
                                decimalPrecision: 3,
                                minValue: 0.001,
                                value: 1.00
                            }
                        ],
                        buttons: [
                            {
                                text: '{s namespace="backend/ratepay" name=ok}Ok{/s}',
                                handler: function () {
                                    var randomnumber = Math.floor(Math.random() * 10001);
                                    var creditname = 'Credit' + id + '-' + randomnumber;
                                    var value = Math.abs(Ext.getCmp('creditAmount').getValue());
                                    if(value <= 0) {
                                        Ext.Msg.alert('Error', '{s namespace="backend/ratepay/messages" name=CreditAmountToLow}{/s}');
                                        return;
                                    }
                                    var label = Ext.getCmp('creditLabel').getValue();
                                    if(label.length === 0) {
                                        Ext.Msg.alert('Error', '{s namespace="backend/ratepay/messages" name=EmptyLabel}{/s}');
                                        return;
                                    }
                                    Ext.Ajax.request({
                                        url: '{url controller=Order action=savePosition}',
                                        method: 'POST',
                                        async: false,
                                        params: {
                                            orderId: id,
                                            articleId: 0,
                                            articleName: label,
                                            articleNumber: creditname,
                                            id: 0,
                                            inStock: 0,
                                            mode: 0,
                                            price: (-1 * value),
                                            quantity: 1,
                                            statusDescription: "",
                                            statusId: 0,
                                            taxDescription: "",
                                            taxId: null,
                                            taxRate: 0,
                                            total: 0,
                                            changed: me.record.get('changed')
                                        },
                                        success: function (payload) {
                                            var response = Ext.JSON.decode(payload.responseText);
                                            if(response.success === false && response.message) {
                                                Shopware.Notification.createGrowlMessage('Error', response.message);
                                                return;
                                            }
                                            var articleNumber = new Array();
                                            var newDetailsId = [];
                                            var message;
                                            articleNumber.push(response.data.articleNumber);
                                            newDetailsId.push(response.data.id);
                                            if (me.initPositions(newDetailsId, 'credit')) {
                                                message = '{s namespace="backend/ratepay/order_management" name="message/successCredit"}{/s}';
                                            } else {
                                                me.deletePosition(newDetailsId);
                                                message = '{s namespace="backend/ratepay/order_management" name="message/failedCredit"}{/s}';
                                            }
                                            Ext.getCmp('creditWindow').close();
                                            Ext.Msg.alert('{s namespace="backend/ratepay/order_management" name="action/addCredit"}{/s}', message);
                                            me.reloadGrid();
                                        }
                                    });
                                }
                            },
                            {
                                text: '{s namespace="backend/application/main" name="detail_window/cancel_button_text"}Cancel{/s}',
                                handler: function () {
                                    Ext.getCmp('creditWindow').close();
                                }
                            }
                        ]
                    }).show();
                }
            },
            {
                iconCls: 'sprite-plus-circle-frame',
                text: '{s namespace="backend/ratepay/order_management" name="action/addDebit"}{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                        title: '{s namespace="backend/ratepay/order_management" name="action/addDebit"}{/s}',
                        width: 300,
                        height: 150,
                        id: 'debitWindow',
                        resizable: false,
                        layout: {
                            type: 'vbox',
                            align : 'stretch',
                            pack  : 'start',
                        },
                        items: [
                            {
                                xtype: 'textfield',
                                id: 'debitLabel',
                                fieldLabel: '{s namespace="backend/ratepay" name="label"}{/s}',
                                allowBlank: false
                            },
                            {
                                xtype: 'numberfield',
                                id: 'debitAmount',
                                fieldLabel: '{s namespace="backend/ratepay" name="amount"}{/s}',
                                helpText: '{s namespace="backend/ratepay" name="precisionNote"}{/s}',
                                allowBlank: false,
                                allowDecimals: true,
                                decimalPrecision: 3,
                                minValue: 0.001,
                                value: 1.00,
                            }
                        ],
                        buttons: [
                            {
                                text: '{s namespace="backend/ratepay" name=ok}Ok{/s}',
                                handler: function () {
                                    var randomnumber = Math.floor(Math.random() * 10001);
                                    var debitname = 'Debit' + id + '+' + randomnumber;
                                    var value = Math.abs(Ext.getCmp('debitAmount').getValue());
                                    if(value <= 0) {
                                        Ext.Msg.alert('Error', '{s namespace="backend/ratepay/messages" name=CreditAmountToLow}{/s}');
                                        return;
                                    }
                                    var label = Ext.getCmp('debitLabel').getValue();
                                    if(label.length === 0) {
                                        Ext.Msg.alert('Error', '{s namespace="backend/ratepay/messages" name=EmptyLabel}{/s}');
                                        return;
                                    }
                                    Ext.Ajax.request({
                                        url: '{url controller=Order action=savePosition}',
                                        method: 'POST',
                                        async: false,
                                        params: {
                                            orderId: id,
                                            articleId: 0,
                                            articleName: label,
                                            articleNumber: debitname,
                                            id: 0,
                                            inStock: 0,
                                            mode: 0,
                                            price: value,
                                            quantity: 1,
                                            statusDescription: "",
                                            statusId: 0,
                                            taxDescription: "",
                                            taxId: null,
                                            taxRate: 0,
                                            total: 0,
                                            changed: me.record.get('changed')
                                        },
                                        success: function (payload) {
                                            var response = Ext.JSON.decode(payload.responseText);
                                            if(response.success === false && response.message) {
                                                Shopware.Notification.createGrowlMessage('Error', response.message);
                                                return;
                                            }
                                            var newDetailIds = [];
                                            var message;
                                            newDetailIds.push(response.data.id);
                                            if (me.initPositions(newDetailIds, 'debit')) {
                                                message = '{s namespace="backend/ratepay/order_management" name="message/successDebit"}{/s}';
                                            } else {
                                                me.deletePosition(newDetailIds);
                                                message = '{s namespace="backend/ratepay/order_management" name="message/failedDebit"}{/s}';
                                            }
                                            Ext.getCmp('debitWindow').close();
                                            Ext.Msg.alert('{s namespace="backend/ratepay/order_management" name="message/addDebit"}{/s}', message);
                                            me.reloadGrid();
                                        }
                                    });
                                }
                            },
                            {
                                text: '{s namespace="backend/application/main" name="detail_window/cancel_button_text"}{/s}',
                                handler: function () {
                                    Ext.getCmp('debitWindow').close();
                                }
                            }
                        ]
                    }).show();
                }
            },
            {
                iconCls: 'sprite-truck',
                text: '{s namespace="backend/ratepay/order_management" name="action/deliver"}{/s}',
                handler: function () {
                    me.toolbarDeliver();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace="backend/ratepay/order_management" name="action/cancel"}{/s}',
                handler: function () {
                    me.toolbarCancel();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace="backend/ratepay/order_management" name="action/cancelStock"}{/s}',
                handler: function () {
                    me.toolbarCancelStock();
                }
            }
        ];
    },

    toolbarDeliver: function () {
        var me = this;
        var items = new Array();
        var id = me.record.get('id');
        var error = false;
        var firstArticle = me.record.getPositions().data.items[0];

        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();
            var tax_rate = row.tax_rate;

            // we must work with numbers. ExtJs collects the data of the row as strings. So we will convert them to integers
            row.quantityReturn = parseInt(row.quantityReturn);
            row.quantityDeliver = parseInt(row.quantityDeliver);
            row.quantity = parseInt(row.quantity);
            row.returned = parseInt(row.returned);
            row.cancelled = parseInt(row.cancelled);

            if (row.quantityDeliver > (row.quantity - row.delivered - row.cancelled)) {
                error = true;
            }
            if (row.tax_rate == null) {
                tax_rate = firstArticle.raw.taxRate;
            }

            item['id'] = row.articleID;
            item['orderDetailId'] = row.orderDetailId;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = tax_rate;
            item['maxQuantity'] = row.quantity;
            item['quantity'] = row.quantityDeliver;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['deliveredItems'] = row.quantityDeliver;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert(
                '{s namespace="backend/ratepay/order_management" name="message/deliverFailed"}{/s}',
                '{s namespace="backend/ratepay/order_management" name="message/deliverTooMuch"}{/s}'
            );
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RatepayOrderDetail action=deliverItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    items: Ext.encode(items)
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    if(response.result && response.message){
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/deliverSuccess"}{/s}',
                            response.message
                        );
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/deliverFailed"}{/s}',
                            response.message
                        );
                    }
                    me.reloadGrid();
                }
            });
        }

    },
    toolbarCancel: function () {
        var me = this;
        var items = new Array();
        var id = me.record.get('id');
        var error = false;
        var firstArticle = me.record.getPositions().data.items[0];

        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();
            var tax_rate = row.tax_rate

            if (row.quantityDeliver > (row.quantity - row.cancelled)) {
                error = true;
            }
            if (row.quantity - row.quantityDeliver - row.cancelled - row.delivered < 0) {
                error = true;
            }

            if (row.tax_rate == null) {
                tax_rate = firstArticle.raw.taxRate;
            }

            item['id'] = row.articleID;
            item['orderDetailId'] = row.orderDetailId;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = tax_rate;
            item['quantity'] = row.quantity - row.quantityDeliver - row.cancelled - row.delivered;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['cancelledItems'] = row.quantityDeliver;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert(
                '{s namespace="backend/ratepay/order_management" name="message/cancelFailed"}{/s}',
                '{s namespace="backend/ratepay/order_management" name="message/cancelTooMuch"}{/s}'
            );
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RatepayOrderDetail action=cancelItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    articleStock: false,
                    items: Ext.encode(items)
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    if(response.result && response.message){
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/cancelSuccess"}{/s}',
                            response.message
                        );
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/cancelFailed"}{/s}',
                            response.message
                        );
                    }
                    me.reloadGrid();
                }
            });
        }
    },
    toolbarCancelStock: function () {
        var me = this;
        var items = new Array();
        var id = me.record.get('id');
        var error = false;
        var firstArticle = me.record.getPositions().data.items[0];

        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();
            var tax_rate = row.tax_rate

            if (row.quantityDeliver > (row.quantity - row.cancelled)) {
                error = true;
            }

            if (row.quantity - row.quantityDeliver - row.cancelled - row.delivered < 0) {
                error = true;
            }

            if (row.tax_rate == null) {
                tax_rate = firstArticle.raw.taxRate;
            }

            item['id'] = row.articleID;
            item['orderDetailId'] = row.orderDetailId;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = tax_rate;
            item['quantity'] = row.quantity - row.quantityDeliver - row.cancelled - row.delivered;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['cancelledItems'] = row.quantityDeliver;
            items.push(item);
        }
        if (error == true) {
            Ext.Msg.alert(
                '{s namespace="backend/ratepay/order_management" name="message/cancelFailed"}{/s}',
                '{s namespace="backend/ratepay/order_management" name="message/cancelTooMuch"}{/s}'
            );
        } else {
            Ext.Ajax.request({
                url: '{url controller=RatepayOrderDetail action=cancelItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    articleStock: 1,
                    items: Ext.encode(items)
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    if(response.result && response.message){
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/cancelSuccess"}{/s}',
                            response.message
                        );
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage(
                            '{s namespace="backend/ratepay/order_management" name="message/cancelFailed"}{/s}',
                            response.message
                        );
                    }
                    me.reloadGrid();
                }
            });
        }
    },

    reloadGrid: function () {
        var me = this;
        var id = me.record.get('id');
        var positionStore = Ext.create('Shopware.apps.RatepayOrder.store.Position');
        me.store = positionStore.load({
            params: {
                'orderId': id
            }
        });

        me.record.store.reload({
            callback: function(records, operation, success) {
                me.record = records.find(function (r) {
                    return r.internalId === me.record.internalId;
                });
            }
        });

        me.reconfigure(me.store);
    },

    initPositions: function (newDetailsId, operationType) {
        var me = this;
        var returnValue = false;
        var id = me.record.get('id');
        Ext.Ajax.request({
            url: '{url controller=RatepayOrderDetail action=add}',
            method: 'POST',
            async: false,
            params: {
                orderId: id,
                detailIds: newDetailsId,
                operationType: operationType
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                if(response.result && response.message){
                    Shopware.Notification.createGrowlMessage('Success', response.message);
                }
                else if(response.result === false && response.message) {
                    Shopware.Notification.createGrowlMessage('Error', response.message);
                }
                returnValue = response.success;
            }
        });
        return returnValue;
    },

    paymentChange: function (orderId, subOperation, insertedIds) {
        var returnValue = false;
        Ext.Ajax.request({
            url: '{url controller=RatepayOrderDetail action=add}',
            method: 'POST',
            async: false,
            params: {
                orderId: orderId,
                suboperation: subOperation,
                insertedIds: Ext.JSON.encode(insertedIds)
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                returnValue = response.result;
            }
        });
        return returnValue;
    },

    deletePosition: function (id) {
        var me = this;
        var orderid = me.record.get('id');
        var result = false;
        Ext.Ajax.request({
            url: '{url controller=Order action=deletePosition targetField=positions}',
            method: 'POST',
            async: false,
            params: {
                orderID: orderid,
                id: id,
                valid: true
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                result = response.success;
            }
        });
        return result;
    }

});
//{/block}
