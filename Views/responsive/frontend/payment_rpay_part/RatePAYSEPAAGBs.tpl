{namespace name="ratepay/frontend/checkout"}

<div id="rpAGB">
    <p class="none">
        <a id="ratepay_sepa_information" onclick="showSepa();" style="cursor: pointer">Einwilligungserklärung zum SEPA-Mandat lesen</a>
        <input type="checkbox" id="ratepay_agb" required="required" checked="checked" aria-required="true" style="display: none;" class="register--checkbox chkbox">
        <span id="ratepay_sepa" style="display: none;">
        {s namespace=RatePAY name='ratepaySEPAAgbFirst'}Ich willige hiermit in die Weiterleitung meiner Daten an RatePAY GmbH, Franklinstraße 28-29, 10587 Berlin gemäß{/s}

        <a href='http://www.ratepay.com/legal' target="_blank" style="text-decoration: underline !important;">RatePAY-Datenschutzerklärung</a>

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
        <input type="checkbox" id="ratepay_agb" required="required" aria-required="true"
               class="register--checkbox chkbox">
        <label for="ratepay_agb">{s name="sepaAgreement"}{/s}<br/>{s name="sepaAgreementNotice"}{/s}</label>
    </p>
</div>

<script type="text/javascript">
    function showSepa() {
        document.getElementById('ratepay_sepa_information').style.display = 'none';
        document.getElementById('ratepay_sepa').style.display = 'block';
    }
</script>
