/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrderHistory.store.History', {
    extend: 'Shopware.store.Listing',
    model: 'Shopware.apps.RatepayOrderHistory.model.History',

    configure: function () {
        return {
            controller: 'RatepayOrderHistory'
        };
    },
});
