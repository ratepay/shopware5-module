{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_fieldset_description' append}

    {if $sUserData.additional.payment.name == 'rpayratepayinvoice' && $payment_mean.name == 'rpayratepayinvoice'}
        {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
    {/if}

    {if $sUserData.additional.payment.name == 'rpayratepaydebit' && $payment_mean.name == 'rpayratepaydebit'}
        {include file='frontend/payment_rpay_part/RatePAYSEPAInformationHeader.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYDebitFormElements.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYSEPAAGBs.tpl'}
    {/if}

    {if $sUserData.additional.payment.name == 'rpayratepayrate' && $payment_mean.name == 'rpayratepayrate'}
        {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
        {include file='frontend/payment_rpay_part/RatePAYRatenrechner.tpl'}
    {/if}

{/block}
