/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
//{block name="backend/order/view/detail/ratepayarticlemanagement"}
Ext.define('Shopware.apps.RatepayOrder.view.detail.tabs.Positions', {

    /**
     * Define that the additional information is an Ext.tab.Panel extension
     * @string
     */
    extend: 'Ext.tab.Panel',

    autoScroll: true,
    layout: 'fit',
    initComponent: function () {
        var me = this;
        me.items = [
            {
                title: '{s namespace="backend/ratepay" name=subtabdelivery}Versand/Stornierung{/s}',
                layout: 'fit',
                items: [
                    Ext.create('Shopware.apps.RatepayOrder.view.detail.positionTabs.Articles', {
                        id: 'deliver',
                        record: me.record
                    })
                ],
                listeners: {
                    activate: function (tab) {
                        var positionStore = Ext.create('Shopware.apps.RatepayOrder.store.Position');
                        var deliveryGrid = Ext.ComponentManager.get('deliver');
                        var store = positionStore.load({
                            params: {
                                'orderId': me.record.get('id')
                            }
                        });
                        deliveryGrid.reconfigure(store);
                    }
                }
            },
            {
                title: '{s namespace="backend/ratepay" name=subtabreturn}Retoure{/s}',
                layout: 'fit',
                items: [
                    Ext.create('Shopware.apps.RatepayOrder.view.detail.positionTabs.Return', {
                        id: 'return',
                        record: me.record
                    })
                ],
                listeners: {
                    activate: function (tab) {
                        var positionStore = Ext.create('Shopware.apps.RatepayOrder.store.Position');
                        var deliveryGrid = Ext.ComponentManager.get('return');
                        var store = positionStore.load({
                            params: {
                                'orderId': me.record.get('id')
                            }
                        });
                        deliveryGrid.reconfigure(store);
                    }
                }
            }
        ];
        this.callParent(arguments);
    }
});
//{/block}
