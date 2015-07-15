<div>
    <p class="none">
        <input type="checkbox" id='ratepay_agb'>
        {s namespace=RatePAY name='ratepaySEPAAgbFirst'}Ich willige hiermit in die Weiterleitung meiner Daten an RatePAY GmbH, Schlüterstr. 39, 10629 Berlin gemäß{/s}



        {if $sUserData.additional.country.countryiso == 'DE'}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank" style="text-decoration: underline !important;">RatePAY-Datenschutzerklärung</a>
        {elseif $sUserData.additional.country.countryiso == 'AT'}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-at' target="_blank" style="text-decoration: underline !important;">RatePAY-Datenschutzerklärung</a>
        {else}
            <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank" style="text-decoration: underline !important;">RatePAY-Datenschutzerklärung</a>
        {/if}

        {s namespace=RatePAY name='ratepaySEPAAgbLast'}
            ein und ermächtige diese, mit diesem Kaufvertrag in Zusammenhang stehende Zahlungen von meinem
            o.a. Konto mittels Lastschrift einzuziehen. Zugleich weise ich mein Kreditinstitut an, die von RatePAY GmbH auf mein Konto gezogenen Lastschriften einzulösen.
            <br/>
            <br/>
            Hinweis:
            <br/>
            Nach Zustandekommen des Vertrags wird mir die Mandatsreferenz von RatePAY mitgeteilt.
            Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrages verlangen.
            Es gelten dabei die mit meinem Kreditinstitut vereinbarten Bedingungen.

        {/s}
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