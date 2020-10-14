{namespace name="frontend/ratepay"}
{if $sPayments && $payment_mean.id == $form_data.payment}
    {block name="ratepay_payment_method"}
        {if $form_data.ratepay == null}
            {block name="ratepay_payment_method__please_wait"}
                <p class="ratepay-redirect-info">{s name="PleaseWait"}{/s}</p>
            {/block}
            <script type="text/javascript">
                window.location.href = window.location.href;
            </script>
        {else}
            {block name="ratepay_payment_method__content"}
                <div class="ratepay-payment-method">
                    {if $ratepay.sandbox}
                        <div class="test-mode-notice" style="">
                            {s name="SandboxIsEnabled"}{/s}
                        </div>
                    {/if}

                    {block name="ratepay_payment_method__fields"}
                        {include file="frontend/plugins/payment/ratepay/common/customer_data.tpl"}
                    {/block}
                    {block name="ratepay_payment_method__toc"}
                        {include file="frontend/plugins/payment/ratepay/common/terms_and_conditions.tpl"}
                    {/block}
                </div>
            {/block}
        {/if}
    {/block}
{/if}
