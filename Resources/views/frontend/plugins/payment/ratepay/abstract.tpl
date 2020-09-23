{namespace name="frontend/ratepay"}
{if $sPayments && $payment_mean.id == $form_data.payment}
    {if $form_data.ratepay == null}
        <p class="ratepay-redirect-info">{s name="PleaseWait"}Einen Augenblick bitte ...{/s}</p>
        <script type="text/javascript">
            window.location.href = window.location.href;
        </script>
    {else}
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
{/if}
