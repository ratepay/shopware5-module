<div class='error' style='display: none;'>
    <div id='ratepay_error'></div>
</div>
<script language='javascript'>
    $(document).ready(function () {
        {if $ratepayValidateisAgeValid != 'true'}
        $("#ratepay_error").append("{s namespace=RatePAY name=invalidAge}Bitte überprüfen sie die Eingabe ihres Geburtstdatums. Sie müssen mindestens 18 Jahre alt sein!{/s}");
        $("#ratepay_error").parent().show();
        {/if}
        {if $ratepayErrorRatenrechner == 'true'}
        $("#ratepay_error").append("{s namespace=RatePAY name=errorRatenrechner}Bitte lassen Sie sich den Ratenplan berechnen!{/s}");
        $("#ratepay_error").parent().show();
        {/if}
    });
</script>