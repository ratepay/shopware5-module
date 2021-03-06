/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{block name="backend/order/application" append}

// Model components
//{include file="backend/ratepay_order/model/position.js"}
//{include file="backend/ratepay_logging/model/log.js"}
//{include file="backend/ratepay_order_history/model/history.js"}

// Store components
//{include file="backend/ratepay_order/store/position.js"}
//{include file="backend/ratepay_logging/store/log.js"}
//{include file="backend/ratepay_order_history/store/history.js"}

// View components
//{include file="backend/ratepay_order/view/detail/window.js"}
//{include file="backend/ratepay_order/view/detail/tabs/position_tabs/articles.js"}
//{include file="backend/ratepay_order/view/detail/tabs/position_tabs/return.js"}
//{include file="backend/ratepay_order/view/detail/tabs/history.js"}
//{include file="backend/ratepay_order/view/detail/tabs/log.js"}
//{include file="backend/ratepay_order/view/detail/tabs/positions.js"}
//{include file="backend/ratepay_order/view/detail/window.js"}

//{/block}
