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

{*
 Compatibility for PremsOneStepCheckout.
 we can not move the container within the #one-page-checkout container, because all childs of it got the same height
 on panel--body of each element. It seems to be that this is a bug of the module.
 When the manufacturer of the extension removes the `data-panel-auto-resizer="true"` from the #one-page-checkout container,
 we can move the installment-plan back to the container.
*}
{block name='frontend_opc_outer_container'}
    {if $useOnePageCheckout && $ratepay.installmentPlan}
        {block name="frontend_opc_confirm__ratepay_installment_plan_wrapper"}
            <div class="panel has--border block opc-ratepay-installment_plan">
                <div class="panel--title is--underline">{$sPayment.description}</div>
                <div class="panel--body is--wide">
                    {block name="frontend_opc_confirm__ratepay_installment_plan_wrapper"}
                        {$ratepay.installmentPlan}
                    {/block}
                </div>
            </div>
        {/block}
    {/if}

    {$smarty.block.parent}
{/block}
