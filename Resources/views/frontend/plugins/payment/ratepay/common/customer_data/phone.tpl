{namespace name="frontend/register/personal_fieldset"}

<input type="hidden" name="ratepay[customer_data][phone_visible]" value="{if $form_data.ratepay.customer_data.phone_visible}1{else}0{/if}">
<input type="hidden" name="ratepay[customer_data][phone_required]" value="{if $form_data.ratepay.customer_data.phone_required}1{else}0{/if}">
{if $form_data.ratepay.customer_data.phone_visible}
    {block name='ratepay_payment_method__phone'}
        <div class="form-group row phone-input">
            <label class="col-sm-2 col-form-label" for="ratepay_phone">
                {s name="RegisterPlaceholderPhone"}{/s}{if $form_data.ratepay.customer_data.phone_required}*{/if}
            </label>
            <div class="col-sm-10">
                <input id="ratepay_phone"
                       class="register--field"
                       name="ratepay[customer_data][phone]"
                       type="text"
                       placeholder="{s name="RegisterPlaceholderPhone"}{/s}"
                       value="{$form_data.ratepay.customer_data.phone}"
                       {if $form_data.ratepay.customer_data.phone_required}required="required"{/if}
                />
            </div>
        </div>
    {/block}
{/if}
