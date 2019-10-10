{extends file="parent:frontend/checkout/error_messages.tpl"}

{* RatePay informations *}
{block name='frontend_checkout_error_messages_basket_error'}
    {if $ratepayMessage}
        {include file="frontend/_includes/messages.tpl" type="error" content=$ratepayMessage}
    {/if}
{/block}
