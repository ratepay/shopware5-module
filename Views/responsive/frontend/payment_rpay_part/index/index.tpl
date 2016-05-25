{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_header_javascript_inline' prepend}
    var ratepayConstantsExists    = true;

    {if $noServerSecure }
        var ratepayUrl                = '{url controller='RpayRatepay' action='saveUserData'}';
    {else}
        var ratepayUrl                = '{url controller='RpayRatepay' action='saveUserData' forceSecure}';
    {/if}

    var userId                    = '{$sUserData.billingaddress.userID}';

    var errorMessageDataComplete  = '{s namespace=RatePAY name=invaliddata}Bitte vervollständigen Sie die Daten.{/s}';
    var errorMessageValidAge      = '{s namespace=RatePAY name=dobtooyoung}Für eine Bezahlung mit RatePAY müssen Sie mindestens 18 Jahre alt sein.{/s}';
    var errorMessageValidPhone    = '{s namespace=RatePAY name=phonenumbernotvalid}Bitte geben Sie eine gültige Telefonnummer an. Die Nummer muss mindestens 6 Zeichen lang sein und darf Sonderzeichen wie - und + enthalten.{/s}';
    var errorMessageValidBankData = '{s namespace=RatePAY name=bankdatanotvalid}Für eine Bezahlung mit RatePay müssen Sie gültige Bankverbindung angeben.{/s}';
    var errorMessageAcceptSepaAGB = '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}';
    var errorMessageCalcRate      = '{s namespace=RatePAY name=errorRatenrechner}Bitte lassen Sie sich den Ratenplan berechnen!{/s}';
    var errorMessageAgeNotValid   = '{s namespace=RatePAY name=invalidAge}Bitte überprüfen sie die Eingabe ihres Geburtstdatums. Sie müssen mindestens 18 Jahre alt sein!{/s}';

    var messageConsoleLogOk       = '{s namespace=RatePAY name=updateUserSuccess}UserDaten erfolgreich aktualisiert.{/s}';
    var messageConsoleLogError    = '{s namespace=RatePAY name=updateUserSuccess}Fehler beim Aktualisieren der UserDaten. Return: {/s}';

    var ratepayAgeNotValid        = false;
    var ratepayCalcRateError      = false;
    var isDebitPayment            = false;

    {if $sUserData.additional.payment.name == 'rpayratepaydebit' }
        var isDebitPayment = true;
    {/if}

    {if $ratepayValidateisAgeValid != 'true'}
        var ratepayAgeNotValid = true;
    {/if}

    {if $ratepayErrorRatenrechner == 'true'}
        var ratepayCalcRateError = true;
    {/if}


{/block}