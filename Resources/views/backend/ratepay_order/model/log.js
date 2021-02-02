/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.model.Log', {
    extend: 'Ext.data.Model',
    fields: ['date', 'version', 'operation', 'subOperation', 'transactionId', 'firstname', 'lastname', 'request', 'response', 'status_code'],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url controller="RatepayLogging" action="loadLogEntries"}'
        },
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
