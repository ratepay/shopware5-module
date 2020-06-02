/*
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
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
                header: '{s namespace=backend/order/main name=column/quantity}Anzahl{/s}',
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
                header: '{s namespace=backend/order/main name=column/article_name}Artikelname{/s}',
                dataIndex: 'name'
            },
            {
                header: '{s namespace=backend/order/main name=column/article_number}Artikelnummer{/s}',
                dataIndex: 'articleordernumber'
            },
            {
                header: '{s namespace=backend/article/view/main name=detail/price/price}Preis{/s}',
                dataIndex: 'price',
                renderer: Ext.util.Format.numberRenderer('0.00')
            },
            {
                header: '{s namespace="backend/ratepay" name=ordered}Bestellt{/s}',
                dataIndex: 'quantity'
            },
            {
                header: '{s namespace=backend/order/main name=overview/shipping/title}Versand{/s}',
                dataIndex: 'delivered'
            },
            {
                header: '{s namespace="backend/ratepay" name=cancelled}Storniert{/s}',
                dataIndex: 'cancelled'
            },
            {
                header: '{s namespace="backend/ratepay" name=returned}Retourniert{/s}',
                dataIndex: 'returned'
            },
        ];
    },

    getToolbar: function () {
        var me = this;
        var id = me.record.get('id');
        return [
            {
                text: '{s namespace="backend/ratepay" name=setzero}Anzahl auf 0 setzen{/s}',
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
                text: '{s namespace="backend/ratepay" name=addcredit}Nachlass hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                        title: '{s namespace="backend/ratepay" name=addcredit}Nachlass hinzuf&uuml;gen{/s}',
                        width: 200,
                        height: 100,
                        id: 'creditWindow',
                        resizable: false,
                        layout: 'fit',
                        items: [
                            {
                                xtype: 'numberfield',
                                id: 'creditAmount',
                                allowBlank: false,
                                allowDecimals: true,
                                minValue: 0.01,
                                value: 1.00
                            }
                        ],
                        buttons: [
                            {
                                text: '{s namespace="backend/ratepay" name=ok}Ok{/s}',
                                handler: function () {
                                    var randomnumber = Math.floor(Math.random() * 10001);
                                    var creditname = 'Credit' + id + '-' + randomnumber;
                                    var firstArticle = me.record.getPositions().data.items[0];
                                    Ext.Ajax.request({
                                        url: '{url controller=Order action=savePosition}',
                                        method: 'POST',
                                        async: false,
                                        params: {
                                            orderId: id,
                                            articleId: 0,
                                            articleName: 'Nachlass',
                                            articleNumber: creditname,
                                            id: 0,
                                            inStock: 0,
                                            mode: 0,
                                            price: (-1 * Math.abs(Ext.getCmp('creditAmount').getValue())),
                                            quantity: 1,
                                            statusDescription: "",
                                            statusId: 0,
                                            taxDescription: "",
                                            taxId: firstArticle.raw.taxId,
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
                                                message = '{s namespace="backend/ratepay" name=messagecreditsuccess}Nachlass wurde erfolgreich zur Bestellung hinzugef&uuml;gt.{/s}';
                                            } else {
                                                me.deletePosition(newDetailsId);
                                                message = '{s namespace="backend/ratepay" name=messagecreditfailrequest}Nachlass konnte nicht korrekt an RatePAY &uuml;bermittelt werden.{/s}';
                                            }
                                            Ext.getCmp('creditWindow').close();
                                            Ext.Msg.alert('{s namespace="backend/ratepay" name=messagecredittitle}Nachlass hinzuf&uuml;gen{/s}', message);
                                            me.reloadGrid();
                                        }
                                    });
                                }
                            },
                            {
                                text: '{s namespace=backend/application/main name=detail_window/cancel_button_text}Cancel{/s}',
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
                text: '{s namespace="backend/ratepay" name=adddebit}Nachbelastung hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                        title: '{s namespace="backend/ratepay" name=adddebit}Nachbelastung hinzuf&uuml;gen{/s}',
                        width: 200,
                        height: 100,
                        id: 'debitWindow',
                        resizable: false,
                        layout: 'fit',
                        items: [
                            {
                                xtype: 'numberfield',
                                id: 'debitAmount',
                                allowBlank: false,
                                allowDecimals: true,
                                minValue: 0.01,
                                value: 1.00
                            }
                        ],
                        buttons: [
                            {
                                text: '{s namespace="backend/ratepay" name=ok}Ok{/s}',
                                handler: function () {
                                    var randomnumber = Math.floor(Math.random() * 10001);
                                    var debitname = 'Debit' + id + '+' + randomnumber;
                                    var firstArticle = me.record.getPositions().data.items[0];
                                    Ext.Ajax.request({
                                        url: '{url controller=Order action=savePosition}',
                                        method: 'POST',
                                        async: false,
                                        params: {
                                            orderId: id,
                                            articleId: 0,
                                            articleName: 'Nachbelastung',
                                            articleNumber: debitname,
                                            id: 0,
                                            inStock: 0,
                                            mode: 0,
                                            price: Math.abs(Ext.getCmp('debitAmount').getValue()),
                                            quantity: 1,
                                            statusDescription: "",
                                            statusId: 0,
                                            taxDescription: "",
                                            taxId: firstArticle.raw.taxId,
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
                                                message = '{s namespace="backend/ratepay" name=messagedebituccess}Nachbelastung wurde erfolgreich zur Bestellung hinzugef&uuml;gt.{/s}';
                                            } else {
                                                me.deletePosition(newDetailIds);
                                                message = '{s namespace="backend/ratepay" name=messagedebitfailposition}Nachbelastung konnte nicht der Bestellung hinzugef&uuml;gt werden.{/s}';
                                            }
                                            Ext.getCmp('debitWindow').close();
                                            Ext.Msg.alert('{s namespace="backend/ratepay" name=messagedebittitle}Nachbelastung hinzuf&uuml;gen{/s}', message);
                                            me.reloadGrid();
                                        }
                                    });
                                }
                            },
                            {
                                text: '{s namespace=backend/application/main name=detail_window/cancel_button_text}Cancel{/s}',
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
                text: '{s namespace="backend/ratepay" name=deliver}Auswahl versenden{/s}',
                handler: function () {
                    me.toolbarDeliver();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace="backend/ratepay" name=cancel}Auswahl stornieren{/s}',
                handler: function () {
                    me.toolbarCancel();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace="backend/ratepay" name=cancelStock}Auswahl stornieren, Inventar aktualisieren{/s}',
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
            Ext.Msg.alert('{s namespace="backend/ratepay" name=messagedeliverytitle}Versand fehlgeschlagen{/s}',
                '{s namespace="backend/ratepay" name=messagedeliverytext}Es k&ouml;nnen nicht mehr Artikel versendet werden als bestellt wurden!{/s}');
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
                        Shopware.Notification.createGrowlMessage('Success', response.message);
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage('Error', response.message);
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
            Ext.Msg.alert('{s namespace="backend/ratepay" name=messagecanceltitle}Stornierung fehlgeschlagen{/s}',
                '{s namespace="backend/ratepay" name=messagecanceltext}Es k&ouml;nnen nicht mehr Artikel storniert werden als bestellt wurden!{/s}');
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
                        Shopware.Notification.createGrowlMessage('Success', response.message);
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage('Error', response.message);
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
            Ext.Msg.alert('{s namespace="backend/ratepay" name=messagecanceltitle}Stornierung fehlgeschlagen{/s}',
                '{s namespace="backend/ratepay" name=messagecanceltext}Es k&ouml;nnen nicht mehr Artikel storniert werden als bestellt wurden!{/s}');
            return false;
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
                        Shopware.Notification.createGrowlMessage('Success', response.message);
                    }
                    else if(response.result === false && response.message) {
                        Shopware.Notification.createGrowlMessage('Error', response.message);
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
