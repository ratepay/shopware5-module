/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging.view.main.Window', {
    extend: 'Enlight.app.Window',
    title: 'Ratepay Logging',
    alias: 'widget.rpay_ratepay_logging-main-window',
    border: false,
    autoShow: true,
    resizable: false,
    layout: {
        type: 'vbox'
    },
    height: 520,
    width: 800,

    initComponent: function () {
        var me = this;
        me.store = me.listStore;
        me.items = [
            me.createOverviewGrid(me),
            me.createDetailGrid(me)
        ];
        me.callParent(arguments);
    },
    createOverviewGrid: function (me) {
        return Ext.create('Ext.grid.Panel', {
            store: me.store,
            forceFit: false,
            border: false,
            height: 266,
            width: '100%',
            columns: [
                {
                    header: '{s namespace=backend/index/view/widgets name=orders/headers/date}Datum{/s}',
                    dataIndex: 'date',
                    flex: 2
                },

                {
                    header: '{s namespace="backend/ratepay" name=version}Version{/s}',
                    dataIndex: 'version',
                    flex: 1
                },

                {
                    header: '{s namespace=backend/article_list/main name=multiEdit/operation}Operation{/s}',
                    dataIndex: 'operation',
                    flex: 2
                },

                {
                    header: '{s namespace="backend/ratepay" name=suboperation}Suboperation{/s}',
                    dataIndex: 'suboperation',
                    flex: 2
                },

                {
                    header: '{s namespace="backend/ratepay" name=transactionid}Transaction-ID{/s}',
                    dataIndex: 'transactionId',
                    flex: 2
                },

                {
                    header: '{s namespace=backend/customer/view/detail name=base/firstname}Firstname{/s}',
                    dataIndex: 'firstname',
                    flex: 1
                },

                {
                    header: '{s namespace=backend/customer/view/detail name=base/lastname}Lastname{/s}',
                    dataIndex: 'lastname',
                    flex: 1
                }
            ],
            dockedItems: [
                {
                    xtype: 'pagingtoolbar',
                    store: me.store,
                    dock: 'bottom',
                    displayInfo: true
                }
            ],
            listeners: {
                itemclick: {
                    fn: function (self, store_record, html_element, node_index, event) {
                        Ext.ComponentManager.get('requestPanel').setValue(store_record.data.request);
                        Ext.ComponentManager.get('responsePanel').setValue(store_record.data.response);
                    }
                }
            }
        });
    },
    createDetailGrid: function (me) {
        return Ext.create('Ext.panel.Panel', {
            width: '100%',
            height: 215,
            border: false,
            layout: {
                type: 'hbox',
                align: 'strech'
            },
            items: [
                {
                    xtype: 'textareafield',
                    border: false,
                    layout: 'fit',
                    title: '{s namespace=backend/application/main name=progress_window/request_header}Request{/s}',
                    value: 'N/A',
                    id: 'requestPanel',
                    autoScroll: true,
                    readOnly: true,
                    width: '50%',
                    height: '100%'
                },
                {
                    xtype: 'textareafield',
                    border: false,
                    layout: 'fit',
                    title: '{s namespace="backend/ratepay" name=response}Response{/s}',
                    value: 'N/A',
                    id: 'responsePanel',
                    autoScroll: true,
                    readOnly: true,
                    width: '50%',
                    height: '100%'
                }
            ]
        });
    }
});
