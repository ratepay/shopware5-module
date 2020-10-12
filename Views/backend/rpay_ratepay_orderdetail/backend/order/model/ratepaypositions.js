/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.Order.model.ratepaypositions', {
    extend: 'Ext.data.Model',
    fields: [ 'name', 'articleID', 'orderDetailId', 'articleordernumber', 'price', 'quantity', 'quantityDeliver', 'quantityReturn', 'delivered', 'cancelled', 'returned', 'tax_rate' ],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller=RpayRatepayOrderDetail action=loadPositionStore}'
        },
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
