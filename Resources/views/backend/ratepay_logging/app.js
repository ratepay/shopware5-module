/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.RatepayLogging',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: ['Main'],

    views: ['main.Window'],

    models: ['Log'],
    stores: ['Log'],

    launch: function () {
        return this.getController('Main').mainWindow;
    }
});
