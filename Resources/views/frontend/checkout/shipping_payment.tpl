{extends file="parent:frontend/checkout/shipping_payment.tpl"}
{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    {if $installmentCalculator}
        <script>
            window.installmentCalculator = true;
             // Prevent conflicts by creating an alias $rp to the jQuery function
        </script>
        <link type="text/css" rel="stylesheet" href="{link file='_public/ratepay/installmentCalculator/css/ratepay-bootstrap.min.css' fullPath}"/>
        <link type="text/css" rel="stylesheet" href="{link file='_public/ratepay/installmentCalculator/css/ratepay-style.css' fullPath}"/>
    {/if}
{/block}
