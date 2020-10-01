/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging', {
    extend: 'Enlight.app.SubApplication',
    name: 'Shopware.apps.RatepayLogging',
    bulkLoad: true,
    loadPath: '{url action=load}',
    controllers: ['Main'],
    models: ['Main'],
    views: ['main.Window'],
    store: ['List'],
    launch: function () {
        var me = this;
        mainController = me.getController('Main');
        return mainController.mainWindow;
    }
});
