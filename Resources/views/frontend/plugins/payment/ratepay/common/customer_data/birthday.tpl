{namespace name="frontend/register/personal_fieldset"}

<input type="hidden" name="ratepay[customer_data][birthday_required]" value="{if $form_data.ratepay.customer_data.birthday_required}1{else}0{/if}">
{if $form_data.ratepay.customer_data.birthday_required != false}
    {block name='ratepay_payment_method__birthday'}
        <div class="ratepay-input birthday-input">
            <label>{s name="RegisterPlaceholderBirthday"}{/s}</label>
            <div class="ratepay-input_value">
                <div class="birthday birthday-day">
                    <label for="ratepay_birthday" class="birthday--label">{s name="RegisterBirthdaySelectDay"}{/s}*</label>
                    <input type="text"
                           id="ratepay_birthday"
                           name="ratepay[customer_data][birthday][day]" maxlength="2"
                           placeholder="{s name="RegisterBirthdaySelectDay"}{/s}"
                           value="{$form_data.ratepay.customer_data.birthday.day}"
                           class="payment--field is--required"
                           required="required" aria-required="true"
                    />
                </div>
                <div class="birthday birthday-month">
                    <label for="ratepay_birthmonth" class="birthday--label">{s name="RegisterBirthdaySelectMonth"}{/s}*</label>
                    <input type="text"
                           id="ratepay_birthmonth"
                           name="ratepay[customer_data][birthday][month]"
                           maxlength="2"
                           placeholder="{s name="RegisterBirthdaySelectMonth"}{/s}"
                           value="{$form_data.ratepay.customer_data.birthday.month}"
                           required="required" aria-required="true"
                    />
                </div>
                <div class="birthday birthday-year">
                    <label for="ratepay_birthyear" class="birthday--label">{s name="RegisterBirthdaySelectYear"}{/s}*</label>
                    <input type="text"
                           id="ratepay_birthyear"
                           name="ratepay[customer_data][birthday][year]"
                           maxlength="4"
                           placeholder="{s name="RegisterBirthdaySelectYear"}{/s}"
                           value="{$form_data.ratepay.customer_data.birthday.year}"
                           required="required" aria-required="true"
                    />
                </div>
            </div>
            <div class="ratepay-input_notice">{s namespace="frontend/ratepay/fields" name="BirthdayInfo"}{/s}</div>
        </div>
    {/block}
{/if}
