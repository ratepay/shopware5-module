{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_footer" append}

    {block name="frontend_ratepay_devicefinterprint"}

        {if $token && $snippetId }
            <script language="JavaScript"><!--//<![CDATA[
                var token     = '{$token}';
                var snippedId = '{$snippetId}';
                {literal}
                var di        = {t: token,v: snippedId,l:'Checkout'};
                {/literal}
                //]]>--></script>
            <script type="text/javascript" src="//d.ratepay.com/{$snippetId}/di.js"></script>
            <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?t={$token}&v={$snippetId}&l=Checkout"></noscript>
        {/if}

    {/block}

{/block}