{extends file="parent:frontend/checkout/confirm.tpl"}
{namespace name="frontend/ratepay"}

{block name='frontend_checkout_confirm_information_wrapper'}
    {if $ratepay.installmentPlan}
        {block name="frontend_checkout_confirm__ratepay_installment_plan_wrapper"}
            <div class="panel has--border block ratepay-installment_plan">
                <div class="panel--title is--underline">{$sPayment.description}</div>
                <div class="panel--body is--wide">
                    {block name="frontend_checkout_confirm__ratepay_installment_plan"}
                        {$ratepay.installmentPlan}
                    {/block}
                </div>
            </div>
        {/block}
    {/if}

    {$smarty.block.parent}
{/block}

{block name="frontend_index_footer"}
    {$smarty.block.parent}

    {block name="frontend_index_footer__ratepay_dfp"}
        {if $ratepay.dfp}
            {$ratepay.dfp}
        {/if}
    {/block}
{/block}
