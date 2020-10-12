/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RpayRatepayLogging.store.List', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: true,
    pageSize: 10,
    model: 'Shopware.apps.RpayRatepayLogging.model.Main'
});
