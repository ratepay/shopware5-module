/**
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 *
 * app
 *
 * @category   RatePAY
 * @package    RpayRatepay
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
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
