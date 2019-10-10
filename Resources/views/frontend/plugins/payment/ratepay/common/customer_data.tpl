{if $payment_mean.id == $form_data.payment}
    {if $ratepay.validation.isBirthdayRequired}
        {include file="frontend/plugins/payment/ratepay/common/customer_data/birthday.tpl"}
    {/if}
    {include file="frontend/plugins/payment/ratepay/common/customer_data/phone.tpl"}
{/if}
