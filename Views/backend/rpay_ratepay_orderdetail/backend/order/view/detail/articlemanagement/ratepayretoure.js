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
//{namespace name=backend/order/main}
//{block name="backend/order/view/detail/articlemanagement/ratepayretoure"}
Ext.define('Shopware.apps.Order.view.detail.ratepayretoure', {

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
        var id = me.record.get('id');

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
                dataIndex: 'quantityReturn',
                editor: {
                    xtype: 'numberfield',
                    hideTrigger: false,
                    allowBlank: false,
                    allowDecimals: false,
                    minValue: 0
                }
            },
            {
                header: '{s namespace=backend/order/main name=column/article_name}Articlename{/s}',
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
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace=RatePAY name=return}Auswahl retournieren{/s}',
                handler: function () {
                    me.toolbarReturn();
                }
            },
            {
                iconCls: 'sprite-minus-circle-frame',
                text: '{s namespace=RatePAY name=returnStock}Auswahl retournieren, Inventar aktuallisieren{/s}',
                handler: function () {
                    me.toolbarReturnStock();
                }
            }
        ];
    },
    toolbarReturn: function () {
        var me = this;
        var items = new Array();
        var id = me.record.get('id');
        var error = false;

        var firstArticle = me.record.getPositions().data.items[0];
        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();

            if (row.quantityReturn > (row.quantity - row.returned - row.cancelled)) {
                error = true;
            }
            if (row.quantityReturn > row.delivered) {
                error = true;
            }

            item['id'] = row.articleID;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = firstArticle.raw.taxRate; // row.tax_rate;
            item['quantity'] = row.delivered - row.returned - row.quantityReturn;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['returnedItems'] = row.quantityReturn;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert('{s namespace=RatePAY name=messagereturntitle}Retoure fehlgeschlagen{/s}',
                '{s namespace=RatePAY name=messagereturntext}Es k&ouml;nnen nicht mehr Artikel retourniert werden als versand wurden!{/s}');
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RpayRatepayOrderDetail action=returnItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    items: Ext.encode(items),
                    articleStock: false
                },
                success: function () {
                    var positionStore = Ext.create('Shopware.apps.Order.store.ratepaypositions');
                    me.store = positionStore.load({
                        params: {
                            'orderId': id
                        }
                    });

                    me.reconfigure(me.store);
                }
            });
        }
    },
    toolbarReturnStock: function () {
        var me = this;
        var items = new Array();
        var id = me.record.get('id');
        var error = false;

        var firstArticle = me.record.getPositions().data.items[0];
        for (i = 0; i < me.store.data.items.length; i++) {
            var row = me.store.data.items[i].data;
            var item = new Object();

            if (row.quantityReturn > (row.quantity - row.returned) || row.delivered == 0) {
                error = true;
            }

            item['id'] = row.articleID;
            item['articlenumber'] = row.articleordernumber;
            item['name'] = row.name;
            item['price'] = row.price;
            item['taxRate'] = firstArticle.raw.taxRate; // row.tax_rate;
            item['quantity'] = row.delivered - row.returned - row.quantityReturn;
            item['delivered'] = row.delivered;
            item['returned'] = row.returned;
            item['cancelled'] = row.cancelled;
            item['returnedItems'] = row.quantityReturn;
            items.push(item);
        }

        if (error == true) {
            Ext.Msg.alert('{s namespace=RatePAY name=messagereturntitle}Retoure fehlgeschlagen{/s}',
                '{s namespace=RatePAY name=messagereturntext}Es k&ouml;nnen nicht mehr Artikel retourniert werden als versand wurden!{/s}');
            return false;
        } else {
            Ext.Ajax.request({
                url: '{url controller=RpayRatepayOrderDetail action=returnItems}',
                method: 'POST',
                async: false,
                params: {
                    orderId: id,
                    items: Ext.encode(items),
                    articleStock: 1
                },
                success: function () {
                    var positionStore = Ext.create('Shopware.apps.Order.store.ratepaypositions');
                    me.store = positionStore.load({
                        params: {
                            'orderId': id
                        }
                    });

                    me.reconfigure(me.store);
                }
            });
        }
    }

});
//{/block}