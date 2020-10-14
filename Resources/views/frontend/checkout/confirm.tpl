{extends file="parent:frontend/checkout/confirm.tpl"}
{namespace name="frontend/ratepay"}

{block name='frontend_checkout_confirm_tos_panel'}
    {$smarty.block.parent}
    {if $ratepay.installmentPlan}
        {block name="frontend_checkout_confirm__ratepay_installment_plan_wrapper"}
            <div class="content--wrapper">
                <div data-register="true" style="width: 100% !important;">
                    <div class="panel has--border is--rounded">
                        <div class="account--billing-form">
                            <div class="panel register--personal">
                                {block name="frontend_checkout_confirm__ratepay_installment_plan_inner"}
                                    <h2 class="panel--title is--underline">{$sPayment.description}</h2>
                                    <div class="panel--body is--wide">
                                        {$ratepay.installmentPlan}
                                    </div>
                                {/block}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/block}
    {/if}
{/block}

{block name="frontend_index_footer"}
    {$smarty.block.parent}

    {block name="frontend_index_footer__ratepay_dfp"}
        {if $ratepay.dfp}
            {$ratepay.dfp}
        {/if}
    {/block}
{/block}
