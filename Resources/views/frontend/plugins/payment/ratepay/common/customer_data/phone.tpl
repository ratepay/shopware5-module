{namespace name="frontend/register/personal_fieldset"}

{block name='ratepay_payment_method__phone'}
    <div class="form-group row phone-input">
        <label class="col-sm-2 col-form-label">
            {s name="RegisterPlaceholderPhone"}{/s}
        </label>
        <div class="col-sm-10">
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
