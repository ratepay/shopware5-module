<div>
    <p class="none">
        <input type="checkbox" id='ratepay_agb'>
        {s namespace=RatePAY name='ratepayAgbFirst'}Hiermit stimme ich der Verwendung meiner Daten gemäß der{/s}

        {if $sUserData.billingaddress.country.iso == 'DE'}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank">RatePAY-Datenschutzerklärung</a>
        {elseif $sUserData.billingaddress.country.iso == 'AT'}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-at' target="_blank">RatePAY-Datenschutzerklärung</a>
        {else}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank">RatePAY-Datenschutzerklärung</a>
        {/if}

        {s namespace=RatePAY name='ratepayAgbLast'} zu und bin insbesondere damit einverstanden, zum Zwecke der Durchführung
            des Vertrages über die von mir angegebene E-Mail-Adresse kontaktiert zu werden.{/s}
    </p>
</div>
<script language='javascript'>
    $(document).ready(function () {
        $('#basketButton').attr('disabled', 'disabled');
        $("#basketButton").css({ opacity: 0.5 });
        $("#basketButton").attr('title', '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}');
        $('#ratepay_agb').click(function () {
            if ($(this).prop('checked')) {
                $("#basketButton").removeAttr('disabled');
                $("#basketButton").removeAttr('title');
                $("#basketButton").css({ opacity: 1.0 });
            } else {
                $("#basketButton").attr('disabled', 'disabled');
                $("#basketButton").attr('title', '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}');
                $("#basketButton").css({ opacity: 0.5 });
            }
        });
    });
</script>