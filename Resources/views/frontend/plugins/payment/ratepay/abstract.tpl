{namespace name="frontend/ratepay"}

{if $payment_mean.id == $form_data.payment}
    <div class="ratepay-payment-method">
        {if $ratepay.sandbox}
            <div class="test-mode-notice" style="">
                {s name="SandboxIsEnabled"}{/s}
            </div>
        {/if}
        {include file="frontend/plugins/payment/ratepay/common/customer_data.tpl"}
        {block name="ratepay_payment_method_content"}{/block}
    </div>
{/if}
