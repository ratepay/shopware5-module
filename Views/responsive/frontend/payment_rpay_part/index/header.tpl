{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_css_print" append}
    <link type="text/css" media="screen, projection" rel="stylesheet"
          href="{link file='engine/Shopware/Plugins/Community/Frontend/RpayRatePay/Views/responsive/frontend/_public/src/styles/ratepay.css' fullPath}"/>
{/block}