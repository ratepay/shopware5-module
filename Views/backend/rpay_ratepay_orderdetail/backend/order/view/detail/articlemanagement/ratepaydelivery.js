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
Ext.define('Shopware.apps.Order.view.detail.ratepaydelivery', {

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
        var positionStore = Ext.create('Shopware.apps.Order.store.ratepaypositions');
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
                header: '{s namespace=RatePAY name=ordered}Bestellt{/s}',
                dataIndex: 'quantity'
            },
            {
                header: '{s namespace=backend/order/main name=overview/shipping/title}Versand{/s}',
                dataIndex: 'delivered'
            },
            {
                header: '{s namespace=RatePAY name=cancelled}Storniert{/s}',
                dataIndex: 'cancelled'
            },
            {
                header: '{s namespace=RatePAY name=returned}Retourniert{/s}',
                dataIndex: 'returned'
            },
        ];
    },

    getToolbar: function () {
        var me = this;
        var id = me.record.get('id');
        return [
            {
                text: '{s namespace=RatePAY name=setzero}Anzahl auf 0 setzen{/s}',
                handler: function () {
                    var id = me.record.get('id');
                    var positionStore = Ext.create('Shopware.apps.Order.store.ratepaypositions');
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
                text: '{s namespace=RatePAY name=addarticle}Artikel hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Shopware.apps.Order.view.detail.ratepayadditemwindow', {
                        parent: me,
                        record: me.record
                    }).show();
                }
            },*/
            {
                iconCls: 'sprite-plus-circle-frame',
                text: '{s namespace=RatePAY name=addcredit}Nachlass hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                        title: '{s namespace=RatePAY name=addcredit}Nachlass hinzuf&uuml;gen{/s}',
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
                                text: '{s namespace=RatePAY name=ok}Ok{/s}',
                                handler: function () {
                                    var randomnumber = Math.floor(Math.random() * 10001);
                                    var creditname = 'Credit' + id + '-' + randomnumber;
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
                                            price: Ext.getCmp('creditAmount').getValue() * -1,
                                            quantity: 1,
                                            statusDescription: "",
                                            statusId: 0,
                                            taxDescription: "",
                                            taxId: 1,
                                            taxRate: 0,
                                            total: 0
                                        },
                                        success: function (payload) {
                                            var response = Ext.JSON.decode(payload.responseText);
                                            var articleNumber = new Array();
                                            var insertedIds = new Array();
                                            var message;
                                            articleNumber.push(response.data.articleNumber);
                                            insertedIds.push(response.data.id);
                                            if (me.initPositions(articleNumber)) {
                                                if (me.paymentChange(id, 'credit', insertedIds)) {
                                                    message = '{s namespace=RatePAY name=messagecreditsuccess}Nachlass wurde erfolgreich zur Bestellung hinzugef&uuml;gt.{/s}';
                                                } else {
                                                    me.deletePosition(insertedIds);
                                                    message = '{s namespace=RatePAY name=messagecreditfailrequest}Nachlass konnte nicht korrekt an RatePAY &uuml;bermittelt werden.{/s}';
                                                }
                                            } else {
                                                message = '{s namespace=RatePAY name=messagecreditfailposition}Nachlass konnte nicht der Bestellung hinzugef&uuml;gt werden.{/s}';
                                            }
                                            Ext.getCmp('creditWindow').close();
                                            Ext.Msg.alert('{s namespace=RatePAY name=messagecredittitle}Nachlass hinzuf&uuml;gen{/s}', message);
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
                text: '{s namespace=RatePAY name=adddebit}Nachbelastung hinzuf&uuml;gen{/s}',
                handler: function () {
                    Ext.create('Ext.window.Window', {
                            title: '{s namespace=RatePAY name=adddebit}Nachbelastung hinzuf&uuml;gen{/s}',
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
                                    text: '{s namespace=RatePAY name=ok}Ok{/s}',
                                    handler: function () {
                                        var randomnumber = Math.floor(Math.random() * 10001);
                                        var debitname = 'Debit' + id + '+' + randomnumber;
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
                                                    price: Ext.getCmp('debitAmount').getValue(),
                                                    quantity: 1,
                                                    statusDescription: "",
                                                    statusId: 0,
                                                    taxDescription: "",
                                                    taxId: 1,
                                                    taxRate: 0,
                                                    total: 0
                                                },
                                            success: function (payload) {
                                                var response = Ext.JSON.decode(payload.responseText);
                                                var articleNumber = new Array();
                                                var insertedIds = new Array();
                                                var message;
                                                articleNumber.push(response.data.articleNumber);
                                                insertedIds.push(response.data.id);
                                                if (me.initPositions(articleNumber)) {
                                                    if (me.paymentChange(id, 'debit', insertedIds)) {
                                                        message = '{s namespace=RatePAY name=messagedebituccess}Nachbelastung wurde erfolgreich zur Bestellung hinzugef&uuml;gt.{/s}';
                                                    } else {
                                                        me.deletePosition(insertedIds);
                                                        message = '{s namespace=RatePAY name=messagedebitfailrequest}Nachbelastung konnte nicht korrekt an RatePAY &uuml;bermittelt werden.{/s}';
                                                    }
                                                } else {
                                                    message = '{s namespace=RatePAY name=messagedebitfailposition}Nachbelastung konnte nicht der Bestellung hinzugef&uuml;gt werden.{/s}';
                                                }
                                                Ext.getCmp('debitWindow').close();
                                                Ext.Msg.alert('{s namespace=RatePAY name=messagedebittitle}Nachbelastung hinzuf&uuml;gen{/s}', message);
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
                text: '{s namespace=RatePAY name=deliver}Auswahl versenden{/s}',
                handler: function () {
                    me.toolbarDeliver();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace=RatePAY name=cancel}Auswahl stornieren{/s}',
                handler: function () {
                    me.toolbarCancel();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace=RatePAY name=cancelStock}Auswahl stornieren, Inventar aktualisieren{/s}',
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
        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();

            if (row.quantityDeliver > (row.quantity - row.delivered - row.cancelled)) {
                error = true;
            }

            item['id'] = row.articleID;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = row.tax_rate;
            item['maxQuantity'] = row.quantity;
            item['quantity'] = row.quantityDeliver;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['deliveredItems'] = row.quantityDeliver;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert('{s namespace=RatePAY name=messagedeliverytitle}Versand fehlgeschlagen{/s}',
                '{s namespace=RatePAY name=messagedeliverytext}Es k&ouml;nnen nicht mehr Artikel versendet werden als bestellt wurden!{/s}');
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RpayRatepayOrderDetail action=deliverItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    items: Ext.encode(items)
                },
                success: function () {
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
        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();

            if (row.quantityDeliver > (row.quantity - row.cancelled)) {
                error = true;
            }
            if (row.quantity - row.quantityDeliver - row.cancelled - row.delivered < 0) {
                error = true;
            }

            item['id'] = row.articleID;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = row.tax_rate;
            item['quantity'] = row.quantity - row.quantityDeliver - row.cancelled - row.delivered;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['cancelledItems'] = row.quantityDeliver;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert('{s namespace=RatePAY name=messagecanceltitle}Stornierung fehlgeschlagen{/s}',
                '{s namespace=RatePAY name=messagecanceltext}Es k&ouml;nnen nicht mehr Artikel storniert werden als bestellt wurden!{/s}');
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RpayRatepayOrderDetail action=cancelItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    articleStock: false,
                    items: Ext.encode(items)
                },
                success: function () {
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
        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();

            if (row.quantityDeliver > (row.quantity - row.cancelled)) {
                error = true;
            }

            if (row.quantity - row.quantityDeliver - row.cancelled - row.delivered < 0) {
                error = true;
            }
            item['id'] = row.articleID;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = row.tax_rate;
            item['quantity'] = row.quantity - row.quantityDeliver - row.cancelled - row.delivered;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['cancelledItems'] = row.quantityDeliver;
            items.push(item);
        }
        if (error == true) {
            Ext.Msg.alert('{s namespace=RatePAY name=messagecanceltitle}Stornierung fehlgeschlagen{/s}',
                '{s namespace=RatePAY name=messagecanceltext}Es k&ouml;nnen nicht mehr Artikel storniert werden als bestellt wurden!{/s}');
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RpayRatepayOrderDetail action=cancelItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    articleStock: 1,
                    items: Ext.encode(items)
                },
                success: function () {
                    me.reloadGrid();
                }
            });
        }
    },

    reloadGrid: function () {
        var me = this;
        var id = me.record.get('id');
        var positionStore = Ext.create('Shopware.apps.Order.store.ratepaypositions');
        me.store = positionStore.load({
            params: {
                'orderId': id
            }
        });

        me.reconfigure(me.store);
    },

    initPositions: function (articleNumber) {
        var returnValue = false;
        var me = this;
        var id = me.record.get('id');
        Ext.Ajax.request({
            url: '{url controller=RpayRatepayOrderDetail action=initPositions}',
            method: 'POST',
            async: false,
            params: {
                orderId: id,
                articleNumber: Ext.JSON.encode(articleNumber)
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                returnValue = response.success;
            }
        });
        return returnValue;
    },

    paymentChange: function (id, suboperation, insertedIds) {
        var returnValue = false;
        Ext.Ajax.request({
            url: '{url controller=RpayRatepayOrderDetail action=add}',
            method: 'POST',
            async: false,
            params: {
                orderId: id,
                suboperation: suboperation,
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