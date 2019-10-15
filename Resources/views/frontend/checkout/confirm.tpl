{extends file="parent:frontend/checkout/confirm.tpl"}
{namespace name="frontend/Ratepay"}

{block name='frontend_checkout_confirm_tos_panel'}
    {$smarty.block.parent}
    {if $ratepay.installmentPlan}
        <div class="content--wrapper">
            <div data-register="true" style="width: 100% !important;">
                <div class="panel has--border is--rounded">
                    <div class="account--billing-form">
                        <div class="panel register--personal">
                            <h2 class="panel--title is--underline">{$sPayment.description}</h2>
                            <div class="panel--body is--wide">
                                {$ratepay.installmentPlan}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/block}

{block name="frontend_index_footer"}
    {$smarty.block.parent}
    {if $ratepay.dfp}
        {$ratepay.dfp}
    {/if}
{/block}
