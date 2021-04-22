/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/order/main}
//{block name="backend/order/view/detail/ratepayhistory"}
Ext.define('Shopware.apps.RatepayOrder.view.detail.tab.History', {

    extend: 'Ext.grid.Panel',
    autoScroll: true,
    listeners: {
        activate: function (tab) {
            this.store.load();
        }
    },

    initComponent: function () {
        var me = this;

        me.store = Ext.create('Shopware.apps.RatepayOrderHistory.store.History', {
            filters: [{
                property: 'orderId',
                value: me.record.get('id')
            }]
        });

        me.columns = {
            items: me.getColumns(),
            defaults: {
                flex: 1
            }
        };

        me.dockedItems = [
            {
                xtype: 'pagingtoolbar',
                store: me.store,
                dock: 'bottom',
                displayInfo: true
            }
        ];

        me.callParent(arguments);

    },
    getColumns: function () {
        return [
            {
                header: '{s namespace=backend/index/view/widgets name=orders/headers/date}Datum{/s}',
                dataIndex: 'date',
                flex: 1,
                xtype: 'datecolumn',
                format: 'd.m.Y H:i:s'
            },
            {
                header: '{s namespace="backend/ratepay" name=event}Event{/s}',
                dataIndex: 'event',
                flex: 2
            },
            {
                header: '{s namespace=backend/article_list/main name=columns/product/Article_name}Name{/s}',
                dataIndex: 'productName',
                flex: 2
            },
            {
                header: '{s namespace=backend/article/view/main name=list/column_number}Nummer{/s}',
                dataIndex: 'productNumber',
                flex: 1
            },
            {
                header: '{s namespace=frontend/checkout/cart_header name=CartColumnQuantity}Anzahl{/s}',
                dataIndex: 'quantity',
                flex: 1
            }
        ];
    }
});
//{/block}
