/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.Order.model.ratepayhistory', {
    extend: 'Ext.data.Model',
    fields: [ 'date', 'event', 'articlename', 'articlenumber', 'quantity'],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller=RpayRatepayOrderDetail action=loadHistoryStore}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
