/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

////{namespace name=backend/order/main}
//{block name="backend/order/view/detail/articlemanagement/ratepayadditemwindow"}
Ext.define('Shopware.apps.Order.view.detail.ratepayadditemwindow', {

    /**
     * Define that the additional information is an Ext.panel.Panel extension
     * @string
     */
    extend: 'Ext.window.Window',
    autoScroll: true,
    layout: 'fit',
    width: 600,
    height: 350,
    title: '{s namespace=RatePAY name=dialogadditemtitle}Artikel hinzuf&uuml;gen{/s}',
    initComponent: function () {
        var me = this;
        me.articleStore = Ext.create('Ext.data.Store', {
            fields: [ 'articleID', 'articleordernumber', 'name', 'quantity', 'price', 'taxRate' ]
        });
        me.items = [
            me.getEmptyGrid(),
        ]
        me.callParent(arguments);
    },

    getEmptyGrid: function () {
        var me = this;
        var id = me.record.get('id');
        return Ext.create('Ext.grid.Panel', {
            plugins: Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            }),
            id: 'gridNewItems',
            columns: me.getColumns(),
            autoScroll: true,
            store: me.articleStore,
            height: 300,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        Ext.create('Shopware.ArticleSearch', {
                            id: 'searchField',
                            multiSelect: false,
                            returnValue: 'name',
                            hiddenReturnValue: 'number',
                            listeners: {
                                scope: me,
                                valueselect: me.onAddArticleToGrid
                            }
                        })
                    ]
                }
            ],
            buttons: [
                {
                    text: '{s namespace=RatePAY name=dialogbuttonadd}Auswahl zur Bestellung hinzufügen{/s}',
                    handler: function () {
                        var store = Ext.getCmp('gridNewItems').getStore();
                        var articleNumber = new Array();
                        var insertedIds = new Array();
                        var message;
                        var del = true;
                        for (i = 0; i < store.data.items.length; i++) {
                            insertedIds.push(me.savePosition(store.data.items[i].data));
                            articleNumber.push(store.data.items[i].data.articleordernumber);
                        }
                        if (me.parent.initPositions(articleNumber)) {
                            if (me.parent.paymentChange(id, 'change-order', insertedIds)) {
                                del = false;
                                message = '{s namespace=RatePAY name=dialogadditemsuccess}Artikel wurden erfolgreich zur Bestellung hinzugefügt.{/s}';
                            } else {
                                message = '{s namespace=RatePAY name=dialogadditemfailrequest}Artikel konnten nicht korrekt an Ratepay übermittelt werden.{/s}';
                            }
                        } else {
                            message = '{s namespace=RatePAY name=dialogadditemfailposition}Artikel konnten nicht der Bestellung hinzugefügt werden.{/s}';
                        }
                        if (del) {
                            for (i = 0; i < insertedIds.length; i++) {
                                me.parent.deletePosition(insertedIds[i]);
                            }
                        }
                        Ext.Msg.alert('{s namespace=RatePAY name=dialogadditemtitle}Artikel hinzuf&uuml;gen{/s}', message);
                        me.parent.reloadGrid();
                        me.close();
                    }
                },
                {
                    text: '{s namespace=backend/order/main name=detail/cancel}Abbrechen{/s}',
                    handler: function () {
                        me.close();
                    }
                }
            ]
        });
    },

    getColumns: function () {
        var me = this;

        return [
            {
                dataIndex: 'articleordernumber',
                header: '{s namespace=backend/order/main name=column/article_number}Artikelnummer{/s}',
                flex: 1
            },
            {
                dataIndex: 'name',
                header: '{s namespace=backend/order/main name=column/article_name}Artikelname{/s}',
                flex: 2
            },
            {
                header: '{s namespace=backend/order/main name=column/quantity}Anzahl{/s}',
                dataIndex: 'quantity',
                editor: {
                    xtype: 'numberfield',
                    hideTrigger: false,
                    allowBlank: false,
                    allowDecimals: false,
                    minValue: 1,
                    value: 1
                },
                flex: 2
            },
            {
                dataIndex: 'price',
                header: '{s namespace=backend/article/view/main name=detail/price/price}Preis{/s}',
                flex: 2,
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowDecimals: true,
                    minValue: 0.01
                },
                renderer: Ext.util.Format.numberRenderer('0.00')
            },
            {
                xtype: 'actioncolumn',
                header: '{s namespace=backend/payment/payment name=formpanel_action_label}Action{/s}',
                width: 60,
                items: [
                    {
                        iconCls: 'sprite-minus-circle',
                        action: 'delete-article',
                        scope: me,
                        handler: function (grid, rowIndex, colIndex, item, eOpts, record) {
                            var store = grid.getStore();
                            store.remove(record);
                        }
                    },
                    {
                        iconCls: 'sprite-inbox',
                        action: 'openArticle',
                        scope: me,
                        /**
                         * Add button handler to fire the openCustomer event which is handled
                         * in the list controller.
                         */
                        handler: function (grid, rowIndex, colIndex, item) {
                            var store = grid.getStore(),
                                record = store.getAt(rowIndex);
                            Shopware.app.Application.addSubApplication({
                                name: 'Shopware.apps.Article',
                                action: 'detail',
                                params: {
                                    articleId: record.get('id')
                                }
                            });
                        }
                    }
                ]
            }
        ];
    },

    onAddArticleToGrid: function (field, returnVal, hiddenVal, record) {
        var me = this, store = me.articleStore;
        var item = me.getItem(record.get('id'));
        store.add(item);
        Ext.getCmp('searchField').searchField.setValue();
    },

    savePosition: function (item) {
        var me = this;
        var id = me.record.get('id');
        var insertID;
        Ext.Ajax.request({
            url: '{url controller=Order action=savePosition}',
            method: 'POST',
            async: false,
            params: {
                orderId: id,
                articleId: item.articleID,
                articleName: item.name,
                articleNumber: item.articleordernumber,
                price: item.price,
                quantity: item.quantity,
                taxRate: item.tax_rate
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                insertID = response.data.id;
            }
        });
        return insertID;
    },

    getItem: function (id) {
        var model;
        Ext.Ajax.request({
            url: '{url controller=RpayRatepayOrderDetail action=getArticle}',
            method: 'POST',
            async: false,
            params: {
                id: id
            },
            success: function (payload) {
                var response = Ext.JSON.decode(payload.responseText);
                model = Ext.create('Shopware.apps.Order.model.ratepaypositions', {
                    articleID: id,
                    articleordernumber: response.data.mainDetail.number,
                    name: response.data.name,
                    quantity: 1,
                    price: response.data.mainPrices[0].price,
                    tax_rate: response.data.tax.tax
                });
            }
        });
        return model;
    }



});
//{/block}
