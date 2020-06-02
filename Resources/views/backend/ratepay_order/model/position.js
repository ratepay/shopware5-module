/*
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.model.Position', {
    extend: 'Ext.data.Model',
    fields: [
        'name',
        'articleID',
        'orderDetailId',
        'articleordernumber',
        'price',
        'quantity',
        'quantityDeliver',
        'quantityReturn',
        'delivered',
        'cancelled',
        'returned'
    ],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller=RatepayOrderDetail action=loadPositionStore}'
        },
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
