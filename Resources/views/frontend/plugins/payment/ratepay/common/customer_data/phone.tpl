{namespace name="frontend/register/personal_fieldset"}

{block name='ratepay_payment_method__phone'}
    <div class="ratepay-input">
        <label for="ratepay_phone">{s name="RegisterPlaceholderPhone"}{/s}</label>
        <div class="ratepay-input_value">
            <input id="ratepay_phone"
                   class="register--field"
                   name="ratepay[customer_data][phone]"
                   type="text"
                   placeholder="{s name="RegisterPlaceholderPhone"}{/s}"
                   value="{$form_data.ratepay.customer_data.phone}"
            />
        </div>
    </div>
{/block}
