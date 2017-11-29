<div id="rpAGB">
    <p class="none">
        <a id="ratepay_sepa_information" onclick="showSepa();" style="cursor: pointer">Einwilligungserklärung zum SEPA-Mandat lesen</a>
        <input type="checkbox" id="ratepay_agb" required="required" checked="checked" aria-required="true" style="display: none;" class="register--checkbox chkbox">
        <span id="ratepay_sepa" style="display: none;">
        {s namespace=RatePAY name='ratepaySEPAAgbFirst'}Ich willige hiermit in die Weiterleitung meiner Daten an RatePAY GmbH, Franklinstraße 28-29, 10587 Berlin gemäß{/s}

        <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach' target="_blank" style="text-decoration: underline !important;">RatePAY-Datenschutzerklärung</a>

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
        </span>
    </p>
</div>

<div id="wicAGB">
    <p class="none">
        <input type="checkbox" id="ratepay_agb" required="required" aria-required="true" class="register--checkbox chkbox">
        <span>
            Ich ermächtige die Wirecard Bank AG von meinem Konto mittels Lastschrift einzuziehen.
            Zugleich weise ich mein Kreditinstitut an, die von der Wirecard Bank AG auf mein Konto gezogenen Lastschriften einzulösen.
            <br/>
            <br/>
            Hinweis:
            <br/>
            Hinweis: Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die
            Erstattung des belasteten Betrages verlangen.Es gelten dabei die mit dem Kreditinstitut vereinbarten Bedingungen.
        </span>
    </p>
</div>

<script type="text/javascript">
    function showSepa() {
        document.getElementById('ratepay_sepa_information').style.display = 'none';
        document.getElementById('ratepay_sepa').style.display = 'block';
    }
</script>