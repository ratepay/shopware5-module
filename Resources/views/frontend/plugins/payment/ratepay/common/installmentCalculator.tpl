{namespace name="frontend/ratepay/installment_calculator"}

{block name="ratepay_payment_method__installment_calculator"}
    <div data-ratepay-installment-calculator="true"
         data-calcRequestUrl="{url controller="RpayRatepay" action="calcRequest"}"
         data-totalAmount="{$sAmount}"
         data-allowBankTransfer="{$ratepay.calculator.rp_debitPayType.rp_paymentType_bankTransfer}"
         data-allowDirectDebit="{$ratepay.calculator.rp_debitPayType.rp_paymentType_directDebit}"
    >
        <input type="hidden" name="rp-calculation-amount" value="{$sAmount}" />
        <input type="hidden" name="ratepay[installment][type]" id="rp-calculation-type" value="{$form_data.ratepay.installment.type}"  />
        <input type="hidden" name="ratepay[installment][value]" id="rp-calculation-value" value="{$form_data.ratepay.installment.value}"  />
        <input type="hidden" name="ratepay[installment][paymentType]" id="rp-payment-type" value="{$form_data.ratepay.installment.paymentType}"  />
        {include file="frontend/plugins/payment/ratepay/installment/calculator.tpl"}
    </div>
{/block}
