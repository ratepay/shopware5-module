/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging.controller.Main', {
    extend: 'Ext.app.Controller',
    mainWindow: null,
    init: function () {
        var me = this;
        me.mainWindow = me.getView('main.Window').create({
            listStore: me.getStore('List').load()
        });
        me.callParent(arguments);
    }
});
