{namespace name="frontend/ratepay/installment_calculator"}

<script>
    var rpInstallmentController = '{url controller="RpayRatepay" action="calcRequest"}';
</script>

<input type="hidden" name="rp-calculation-amount" id="rp-calculation-amount" value="{$installmentCalculator.totalAmount}" />
<input type="hidden" name="ratepay[installment][type]" id="rp-calculation-type" />
<input type="hidden" name="ratepay[installment][value]" id="rp-calculation-value" />
<input type="hidden" name="ratepay[installment][payment_type]" id="rp-payment-type" />
<input type="hidden" name="ratepay[installment][payment_firstday]" id="rp-payment-firstday" />
{$installmentCalculator.html}

