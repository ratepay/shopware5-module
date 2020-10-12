{namespace name="ratepay/frontend/checkout"}

<div class="sepa-legal">
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
