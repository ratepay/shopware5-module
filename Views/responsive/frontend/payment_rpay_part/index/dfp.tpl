{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_footer"}
    {$smarty.block.parent}

    {block name="frontend_ratepay_devicefingerprint"}
        {if $token && $snippetId }
            <script type="text/javascript">
                window.di = {
                    t: '{$token}',
                    v: '{$snippetId}',
                    l:'Checkout'
                };
            </script>
            <script type="text/javascript" src="//d.ratepay.com/{$snippetId}/di.js"></script>
            <noscript>
                <link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?t={$token}&v={$snippetId}&l=Checkout" />
            </noscript>
        {/if}
    {/block}

{/block}
