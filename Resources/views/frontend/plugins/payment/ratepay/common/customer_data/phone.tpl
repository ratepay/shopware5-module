{namespace name="frontend/register/personal_fieldset"}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="form-group row phone-input">
        <label class="col-sm-2 col-form-label">
            {s name="RegisterPlaceholderPhone"}Telefonnummer{/s}*
        </label>
        <div class="col-sm-10">
            <input id="ratepay_phone"
                   class="register--field is--required"
                   name="ratepay[customer_data][phone]"
                   type="text"
                   placeholder="{s name="RegisterPlaceholderPhone"}Telefonnummer{/s}"
                   value="{$form_data.ratepay.customer_data.phone}"
                   required="required"
                   aria-required="true"
            />
        </div>
    </div>
{/block}
