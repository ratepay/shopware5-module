/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
