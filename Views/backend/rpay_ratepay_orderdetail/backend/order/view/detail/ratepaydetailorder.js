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
Ext.define('Shopware.apps.Order.view.detail.ratepaydetailorder', {

    override: 'Shopware.apps.Order.view.detail.Window',

    //Überschreiben der standard funktion welche das Tab Panel erzeugt.
    createTabPanel: function () {
        var me = this;
        var tabPanel = me.callParent(arguments);

        if (me.isRatePAYOrder()) {
            tabPanel = me.createRatePAYTabPanel();
        }

        return tabPanel;
    },
    isRatePAYOrder: function () {
        var me = this;
        var paymentName = '';
        for (i = 0; i < me.paymentsStore.data.items.length; i++) {
            if (me.paymentsStore.data.items[i].data.id == this.record.get('paymentId')) {
                paymentName = me.paymentsStore.data.items[i].data.name;
            }
        }

        if (paymentName.search(/^rpayratepay(invoice|rate|debit)$/) != -1) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * Creates the tab panel for the detail page.
     * @return Ext.tab.Panel
     */
    createRatePAYTabPanel: function () {
        var me = this;

        return Ext.create('Ext.tab.Panel', {
            name: 'main-tab',
            items: [
                Ext.create('Shopware.apps.Order.view.detail.Overview', {
                    title: me.snippets.overview,
                    record: me.record,
                    orderStatusStore: me.orderStatusStore,
                    paymentStatusStore: me.paymentStatusStore
                }), Ext.create('Shopware.apps.Order.view.detail.Detail', {
                    title: me.snippets.details,
                    record: me.record,
                    paymentsStore: me.paymentsStore,
                    shopsStore: me.shopsStore,
                    countriesStore: me.countriesStore
                }), Ext.create('Shopware.apps.Order.view.detail.Communication', {
                    title: me.snippets.communication,
                    record: me.record
                }), Ext.create('Shopware.apps.Order.view.detail.Document', {
                    record: me.record,
                    documentTypesStore: me.documentTypesStore
                }), Ext.create('Shopware.apps.Order.view.detail.OrderHistory', {
                    title: me.snippets.history,
                    historyStore: me.historyStore,
                    record: me.record,
                    orderStatusStore: me.orderStatusStore,
                    paymentStatusStore: me.paymentStatusStore
                }), Ext.create('Shopware.apps.Order.view.detail.ratepayarticlemanagement', {
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
            ]
        });
    }

});
//{/block}
