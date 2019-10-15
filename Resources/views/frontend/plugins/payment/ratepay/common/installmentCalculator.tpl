{namespace name="frontend/ratepay/installment_calculator"}

<script>
    window.installmentCalculator = true;
    window.rpInstallmentController = '{url controller="RpayRatepay" action="calcRequest"}';
</script>

<input type="hidden" name="rp-calculation-amount" id="rp-calculation-amount" value="{$installmentCalculator.totalAmount}" />
<input type="hidden" name="ratepay[installment][type]" id="rp-calculation-type" />
<input type="hidden" name="ratepay[installment][value]" id="rp-calculation-value" />
<input type="hidden" name="ratepay[installment][paymentType]" id="rp-payment-type" />
<input type="hidden" name="ratepay[installment][paymentFirstDay]" id="rp-payment-firstday" />
{$installmentCalculator.html}

