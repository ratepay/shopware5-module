{if $sPayment.name == 'rpayratepaydebit'}
    {include file='frontend/RatePAYSEPAInformationHeader.tpl'}
    {include file='frontend/RatePAYErrorMessage.tpl'}
    {include file='frontend/RatePAYFormElements.tpl'}
    {include file='frontend/RatePAYDebitFormElements.tpl'}
    {include file='frontend/RatePAYSEPAAGBs.tpl'}
{/if}
