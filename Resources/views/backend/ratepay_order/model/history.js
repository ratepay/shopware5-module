/*
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.model.History', {
    extend: 'Ext.data.Model',
    fields: [ 'date', 'event', 'articlename', 'articlenumber', 'quantity'],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller=RatepayOrderDetail action=loadHistoryStore}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
