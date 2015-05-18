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
 * list
 *
 * @category   RatePAY
 * @package    RpayRatepay
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
Ext.define('Shopware.apps.RpayRatepayLogging.store.List', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: true,
    pageSize: 10,
    model: 'Shopware.apps.RpayRatepayLogging.model.Main'
});