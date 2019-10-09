{namespace name="frontend/register/personal_fieldset"}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="rp-birthday field--select">
        <label for="ratepay_phone" class="birthday--label">{s name="RegisterPlaceholderPhone"}Telefonnummer{/s}*</label>
        <br/>
        <input id="ratepay_phone"
               class="register--field is--required"
               name="ratepay[customer_data][phone]"
               type="text"
               placeholder="{s name="RegisterPlaceholderPhone"}Telefonnummer{/s}"
               value="{$ratepay.customerData.phone}"
               required="required"
               aria-required="true"
        />
    </div>
{/block}
