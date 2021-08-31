{extends file="parent:frontend/detail/data.tpl"}

{block name="frontend_detail_data" append}
    {if $ratepay.installment}
        {block name="frontend_detail_data_ratepay_installment"}
            <div class="ratepay--installment">
                {if $ratepay.installment.isZeroPercent}
                    {s name="RatepayZeroPercentInstallment"}{/s}
                {else}
                    {s name="RatepayInstallment"}{/s}
                {/if}
            </div>
        {/block}
    {/if}
{/block}
