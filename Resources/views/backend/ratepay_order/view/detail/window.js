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
                title: '{s namespace=RatePAY name=tabarticlemanagement}RatePAY Artikelverwaltung{/s}',
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }),
            Ext.create('Shopware.apps.RatepayOrder.view.detail.tabs.Log', {
                title: '{s namespace=RatePAY name=tablog}RatePAY Log{/s}',
                record: me.record
            }),
            Ext.create('Shopware.apps.RatepayOrder.view.detail.tab.History', {
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
