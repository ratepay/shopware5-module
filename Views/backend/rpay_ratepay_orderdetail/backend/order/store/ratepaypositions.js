/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.Order.store.ratepaypositions', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: false,
    model: 'Shopware.apps.Order.model.ratepaypositions'
});
