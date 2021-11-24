{namespace name="frontend/ratepay/installment_calculator"}

{block name="ratepay_payment_method__installment_calculator"}
    <div data-ratepay-installment-calculator="true"
         data-calcRequestUrl="{url controller="RpayRatepay" action="calcRequest"}"
         data-totalAmount="{$sAmount}"
    >
        <input type="hidden" name="rp-calculation-amount" value="{$sAmount}" />
        <input type="hidden" name="ratepay[installment][paymentMethodId]" id="rp-calculation-type" value="{$form_data.payment}"  />
        <input type="hidden" name="ratepay[installment][calculation_type]" id="rp-calculation-type" value="{$form_data.ratepay.installment.type}"  />
        <input type="hidden" name="ratepay[installment][calculation_value]" id="rp-calculation-value" value="{$form_data.ratepay.installment.value}"  />
        {include file="frontend/plugins/payment/ratepay/installment/calculator.tpl"}
    </div>
{/block}
