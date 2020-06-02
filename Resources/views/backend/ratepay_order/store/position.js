/*
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrder.store.Position', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: false,
    model: 'Shopware.apps.RatepayOrder.model.Position'
});
