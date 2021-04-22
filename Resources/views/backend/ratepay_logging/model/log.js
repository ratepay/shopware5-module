/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging.model.Log', {
    extend: 'Ext.data.Model',
    fields: ['date', 'version', 'operation', 'subOperation', 'transactionId', 'firstname', 'lastname', 'request', 'response', 'status_code'],

    configure: function () {
        return {
            controller: 'RatepayLogging'
        };
    },
});
