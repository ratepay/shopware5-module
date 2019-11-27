{namespace name="frontend/ratepay/installment_calculator"}
<div data-ratepay-installment-calculator="true" data-calcRequestUrl="{url controller="RpayRatepay" action="calcRequest"}">
    <input type="hidden" name="rp-calculation-amount" id="rp-calculation-amount" value="{$sAmount}" />
    <input type="hidden" name="ratepay[installment][type]" id="rp-calculation-type" value="{$form_data.ratepay.installment.type}"  />
    <input type="hidden" name="ratepay[installment][value]" id="rp-calculation-value" value="{$form_data.ratepay.installment.value}"  />
    <input type="hidden" name="ratepay[installment][paymentType]" id="rp-payment-type" value="{$form_data.ratepay.installment.paymentType}"  />
    <input type="hidden" name="ratepay[installment][paymentFirstDay]" id="rp-payment-firstday" value="{$form_data.ratepay.installment.paymentFirstDay}"  />
    {$ratepay.installmentCalculator}
</div>
