/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
Ext.define('Shopware.apps.RatepayLogging.controller.Main', {
    extend: 'Ext.app.Controller',

    init: function () {
        this.mainWindow = this.getView('main.Window').create({ }).show();
    }
});
