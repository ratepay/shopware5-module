{namespace name="frontend/ratepay"}

{if $payment_mean.id == $form_data.payment}
    {include file="frontend/plugins/payment/ratepay/common/customer_data.tpl"}
    {block name="ratepay_payment_method_content"}{/block}
    {if $ratepay.sandbox}
        <div class="tos--panel" style="border: 1px dashed black; padding: 5px; background-color: yellow">
            {s name="SandboxIsEnabled"}{/s}
        </div>
    {/if}
{/if}
