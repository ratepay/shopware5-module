{extends file='frontend/plugins/payment/ratepay/abstract.tpl'}

{block name="ratepay_payment_method__fields"}
    {$smarty.block.parent}
    {include file="frontend/plugins/payment/ratepay/common/installmentCalculator.tpl"}
{/block}
