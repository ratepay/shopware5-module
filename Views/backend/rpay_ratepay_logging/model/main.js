/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RpayRatepayLogging.model.Main', {
    extend: 'Ext.data.Model',
    fields: [ 'date', 'version', 'operation', 'suboperation', 'transactionId', 'firstname', 'lastname', 'request', 'response'],
    proxy: {
        type: 'ajax',
        api: {
            read: '{url action=loadStore}'
        },
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
