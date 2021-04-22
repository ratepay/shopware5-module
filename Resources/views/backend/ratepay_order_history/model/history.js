/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrderHistory.model.History', {
    extend: 'Ext.data.Model',
    fields: [
        'date',
        'event',
        'productName',
        'productNumber',
        'quantity'
    ],
});
