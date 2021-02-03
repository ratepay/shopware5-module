/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{block name="backend/order/view/detail/window" append}
//{namespace name=backend/order/view/main}
Ext.define('Shopware.apps.RatepayOrder.view.detail.Window', {

    override: 'Shopware.apps.Order.view.detail.Window',

    createTabPanel: function () {
        var me = this;
        var tabPanel = me.callParent(arguments);

        if (me.isRatePAYOrder()) {
            tabPanel = me.createRatePAYTabPanel(tabPanel);
        }

        return tabPanel;
    },
    isRatePAYOrder: function () {
        var me = this;
        var paymentName = '';
        var payments = me.paymentsStore.data.items;
        for (i = 0; i < payments.length; i++) {
            if (payments[i].data.id == this.record.get('paymentId')) {
                paymentName = payments[i].data.name;
            }
        }

        return (paymentName.search(/^rpayratepay(invoice|rate|rate0|debit|prepayment)$/) !== -1);
    },

    /**
     * Adds the tab panel for the detail page.
     * @return Ext.tab.Panel
     */
    createRatePAYTabPanel: function (tabPanel) {
        var me = this;

        tabPanel.add([
            Ext.create('Shopware.apps.RatepayOrder.view.detail.tabs.Positions', {
                title: '{s namespace="backend/ratepay" name="TabPositions"}Ratepay Artikelverwaltung{/s}',
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }),
            Ext.create('Shopware.apps.RatepayOrder.view.detail.tabs.Log', {
                title: '{s namespace="backend/ratepay" name=tablog}Ratepay Log{/s}',
                record: me.record
            }),
            Ext.create('Shopware.apps.RatepayOrder.view.detail.tab.History', {
                title: '{s namespace="backend/ratepay" name=tabhistory}Ratepay History{/s}',
                historyStore: me.historyStore,
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            })
        ]);

        return tabPanel;
    }

});
//{/block}
