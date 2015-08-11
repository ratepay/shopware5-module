{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_footer" append}

    {block name="frontend_ratepay_devicefinterprintident"}

        {if $token && $snippetId }

            <script language="JavaScript">
                var token     = '{$token}';
                var snippedId = '{$snippetId}';
                {literal}
                var di        = {t: token,v: snippedId,l:'Checkout'};
                {/literal}
            </script>
            <script type="text/javascript" src="//d.ratepay.com/{$snippetId}/di.js"></script>
            <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?t={$token}&v={$snippetId}&l=Checkout"></noscript>
            <object type="application/x-shockwave-flash" data="//d.ratepay.com/{$snippetId}/c.swf" width="0" height="0">
                <param name="movie" value="//d.ratepay.com/{$snippetId}/c.swf" />
                <param name="flashvars" value="t={$token}&v={$snippetId}&l=Checkout"/>
                <param name="AllowScriptAccess" value="always"/>
            </object>


        {/if}

    {/block}

{/block}