/*
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.store.History', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: false,
    pageSize: 25,
    model: 'Shopware.apps.RatepayOrder.model.History'
});
