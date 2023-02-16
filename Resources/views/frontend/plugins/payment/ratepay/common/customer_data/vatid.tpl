{namespace name="frontend/register/billing_fieldset"}

<input type="hidden" name="ratepay[customer_data][vatId_required]" value="{if $form_data.ratepay.customer_data.vatId_required}1{else}0{/if}">
{if $form_data.ratepay.customer_data.vatId_required != false}
    {block name='ratepay_payment_method__vatid'}
        <div class="ratepay-input vatid-input">
            <label for="ratepay_vatid">{s name="RegisterLabelTaxId"}{/s}</label>
            <div class="ratepay-input_value">
                <input id="ratepay_vatid"
                           class="register--field is--required"
                           name="ratepay[customer_data][vatId]"
                           type="text"
                           placeholder="{s name='RegisterLabelTaxId'}{/s}"
                           value="{$form_data.ratepay.customer_data.vatId}"
                    />
            </div>
        </div>
    {/block}
{/if}
