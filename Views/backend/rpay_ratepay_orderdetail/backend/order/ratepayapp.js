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
 */
//{block name="backend/order/application" append}
//Include History
//{include file="backend/order/store/ratepayhistory.js"}
//{include file="backend/order/model/ratepayhistory.js"}
//{include file="backend/order/view/detail/ratepayhistory.js"}

//Include ArticleOverview
//{include file="backend/order/store/ratepaypositions.js"}
//{include file="backend/order/model/ratepaypositions.js"}
//{include file="backend/order/view/detail/articlemanagement/ratepaydelivery.js"}
//{include file="backend/order/view/detail/articlemanagement/delivery/ratepayadditemwindow.js"}
//{include file="backend/order/view/detail/articlemanagement/ratepayretoure.js"}
//{include file="backend/order/view/detail/ratepayarticlemanagement.js"}

//Include Log
//{include file="backend/order/store/ratepaylog.js"}
//{include file="backend/order/model/ratepaylog.js"}
//{include file="backend/order/view/detail/ratepaylog.js"}

//Incluce RatePay-OrderDetail
//{include file="backend/order/view/detail/ratepaydetailorder.js"}
//{/block}
