/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.store.Log', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: false,
    pageSize: 25,
    model: 'Shopware.apps.RatepayOrder.model.Log'
});
