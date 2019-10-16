{namespace name="frontend/register/personal_fieldset"}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="rp-birthday field--select row">
        <div class="col-xs-12 col-sm-3">
            <label for="ratepay_phone" class="birthday--label">{s name="RegisterPlaceholderPhone"}Telefonnummer{/s}*</label>
        </div>
        <div class="col-xs-12 col-sm-6">
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
