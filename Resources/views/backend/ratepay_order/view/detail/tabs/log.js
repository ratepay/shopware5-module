/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/order/main}
//{block name="backend/order/view/detail/ratepaylog"}
Ext.define('Shopware.apps.RatepayOrder.view.detail.tabs.Log', {

    /**
     * Define that the additional information is an Ext.panel.Panel extension
     * @string
     */
    extend: 'Ext.grid.Panel',
    autoScroll: true,
    listeners: {
        activate: function (tab) {
            this.store.load();
        }
    },

    initComponent: function () {
        var me = this;

        me.columns = {
            items: me.getColumns(),
            defaults: {
                flex: 1
            }
        };

        me.store = Ext.create('Shopware.apps.RatepayLogging.store.Log', {
            filters: [{
                property: 'transactionId',
                value: me.record.get('transactionId')
            }]
        });

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
                header: '{s namespace="backend/index/view/widgets" name="orders/headers/date"}Datum{/s}',
                dataIndex: 'date',
                flex: 2,
                xtype: 'datecolumn',
                format: 'd.m.Y H:i:s'
            },

            {
                header: '{s namespace="backend/ratepay" name="version"}Version{/s}',
                dataIndex: 'version',
                flex: 1
            },

            {
                header: '{s namespace="backend/article_list/main" name="multiEdit/operation"}Operation{/s}',
                dataIndex: 'operation',
                flex: 2
            },

            {
                header: '{s namespace="backend/ratepay" name="suboperation"}Suboperation{/s}',
                dataIndex: 'subOperation',
                flex: 2
            },
            {
                header: '{s namespace="backend/ratepay" name="status"}Status{/s}',
                dataIndex: 'status_code',
                flex: 2
            },
            {
                header: '{s namespace="backend/ratepay" name="transactionid"}Transaction-ID{/s}',
                dataIndex: 'transactionId',
                flex: 2
            },
            {
                header: '{s namespace="backend/customer/view/detail" name="base/firstname"}FirstName{/s}',
                dataIndex: 'firstname',
                flex: 1
            },
            {
                header: '{s namespace="backend/customer/view/detail" name="base/lastname"}Lastname{/s}',
                dataIndex: 'lastname',
                flex: 1
            },
            {
                header: '{s namespace="backend/application/main" name="progress_window/request_header"}Request{/s}',
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        iconCls: 'sprite-documents-stack',
                        handler: function (grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.create('Ext.window.Window', {
                                title: 'Request-XML',
                                height: 350,
                                width: 450,
                                layout: 'fit',
                                items: {
                                    xtype: 'textareafield',
                                    readOnly: true,
                                    grow: false,
                                    value: rec.get('request')
                                }
                            }).show();
                        }
                    }
                ]
            },
            {
                header: '{s namespace="backend/ratepay" name="response"}Response{/s}',
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        iconCls: 'sprite-documents-stack',
                        handler: function (grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.create('Ext.window.Window', {
                                title: 'Response-XML',
                                height: 350,
                                width: 450,
                                layout: 'fit',
                                items: {
                                    xtype: 'textareafield',
                                    readOnly: true,
                                    grow: false,
                                    value: rec.get('response')
                                }
                            }).show();
                        }
                    }
                ]
            }
        ];
    }
});
//{/block}
