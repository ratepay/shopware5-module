{extends file="parent:frontend/checkout/error_messages.tpl"}

{* RatePay informations *}
{block name='frontend_checkout_error_messages_basket_error'}
    {foreach from=$ratePayMessages item=message}
        {include file="frontend/_includes/messages.tpl" type=$message.type content=$message.message}
    {/foreach}
{/block}
