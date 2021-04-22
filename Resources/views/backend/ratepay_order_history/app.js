/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayOrderHistory', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.RatepayOrderHistory',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: ['History'],
    models: ['History'],
    stores: ['History'],
});
