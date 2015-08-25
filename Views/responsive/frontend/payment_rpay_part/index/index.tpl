{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_header_javascript_inline' prepend}
    var ratepayUrl                = '{url controller='RpayRatepay' action='saveUserData'}';
    var userId                    = '{$sUserData.billingaddress.userID}';
    var errorMessageDataComplete  = '{s namespace=RatePAY name=invaliddata}Bitte vervollständigen Sie die Daten.{/s}';
    var errorMessageValidAge      = '{s namespace=RatePAY name=dobtooyoung}Für eine Bezahlung mit RatePAY müssen Sie mindestens 18 Jahre alt sein.{/s}';
    var errorMessageValidPhone    = '{s namespace=RatePAY name=phonenumbernotvalid}Für eine Bezahlung mit RatePay müssen Sie eine gültige Telefonnummer angeben. Die Nummer muss mindestens 6 Zeichen lang sein und darf Sonderzeichen wie - und + enthalten.{/s}';
    var errorMessageValidBankData = '{s namespace=RatePAY name=bankdatanotvalid}Für eine Bezahlung mit RatePay müssen Sie gültige Bankverbindung angeben.{/s}';
    var messageConsoleLogOk       = '{s namespace=RatePAY name=updateUserSuccess}UserDaten erfolgreich aktualisiert.{/s}';
    var messageConsoleLogError    = '{s namespace=RatePAY name=updateUserSuccess}Fehler beim Aktualisieren der UserDaten. Return: {/s}';
    var errorMessageAcceptSepaAGB = '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}';
    {if $sUserData.additional.payment.name == 'rpayratepaydebit' }
        var isDebitPayment = true;
    {else}
        var isDebitPayment = false;
    {/if}
{/block}

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