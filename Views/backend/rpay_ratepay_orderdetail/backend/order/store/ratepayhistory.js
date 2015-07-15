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
 * ratepayhistory
 *
 * @category   RatePAY
 * @package    RpayRatepay
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
Ext.define('Shopware.apps.Order.store.ratepayhistory', {
    extend: 'Ext.data.Store',
    autoLoad: false,
    remoteSort: false,
    pageSize: 25,
    model: 'Shopware.apps.Order.model.ratepayhistory'
});