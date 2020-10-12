/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{block name="backend/order/view/detail/window" append}
//{namespace name=backend/order/view/main}
Ext.define('Shopware.apps.Order.view.detail.ratepaydetailorder', {

    override: 'Shopware.apps.Order.view.detail.Window',

    //Überschreiben der standard funktion welche das Tab Panel erzeugt.
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
        var counter = 0;
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
            Ext.create('Shopware.apps.Order.view.detail.ratepayarticlemanagement', {
                title: '{s namespace=RatePAY name=tabarticlemanagement}Artikelverwaltung{/s}',
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }), Ext.create('Shopware.apps.Order.view.detail.ratepaylog', {
                title: '{s namespace=RatePAY name=tablog}RatePAY Log{/s}',
                record: me.record
            }), Ext.create('Shopware.apps.Order.view.detail.ratepayhistory', {
                title: '{s namespace=RatePAY name=tabhistory}RatePAY History{/s}',
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
