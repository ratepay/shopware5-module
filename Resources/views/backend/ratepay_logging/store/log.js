/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging.store.Log', {
    extend: 'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'RatepayLogging'
        };
    },

    model: 'Shopware.apps.RatepayLogging.model.Log'
});
