{namespace name="frontend/register/personal_fieldset"}
{* Birthday *}
{block name='ratepay_frontend_birthday'}
    <div class="register--birthdate">
        <label for="register_personal_birthdate" class="birthday--label">
            <strong>{s name="RegisterPlaceholderBirthday"}Geburtsdatum{/s}</strong>
        </label>
        <br/>

        <div class="rp-birthday field--select">
            <label for="register_personal_birthdate" class="birthday--label">
                {s name="RegisterPlaceholderBirthdayTag"}Tag{/s}*
            </label>
            <br/>
            <input type="text"
                   id="ratepay_birthday"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                   name="ratepay[customer_data][birthday][day]" maxlength="2"
                   placeholder="{s name="RegisterPlaceholderBirthdayTag"}Tag{/s}"
                   value="{$ratepay.customerData.birthday.day}"
                   class="payment--field is--required{if $error_flags.mopt_payone__ratepay_invoice_telephone} has--error{/if}"
            />
        </div>

        <div class="rp-birthmonth field--select">
            <label for="register_personal_birthdate" class="birthday--label">
                {s name="RegisterPlaceholderBirthdayMonth"}Monat{/s}*
            </label>
            <br/>
            <input type="text"
                    id="ratepay_birthmonth"
                    name="ratepay[customer_data][birthday][month]"
                    maxlength="2"
                    placeholder="{s name="RegisterPlaceholderBirthdayMonth"}Monat{/s}"
                    value="{$ratepay.customerData.birthday.month}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            />
        </div>

        <div class="rp-birthyear field--select">
            <label for="register_personal_birthdate" class="birthday--label">
                {s name="RegisterPlaceholderBirthdayYear"}Jahr{/s}*
            </label>
            <br/>
            <input type="text"
                   id="ratepay_birthyear"
                   name="ratepay[customer_data][birthday][year]"
                   maxlength="4"
                   placeholder="{s name="RegisterPlaceholderBirthdayYear"}Jahr{/s}"
                   value="{$ratepay.customerData.birthday.year}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            />
        </div>
        <br style="clear: both">
        <p>
            <small>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um per {$sPayment.description} bezahlen zu können.{/s}</small>
        </p>
    </div>
{/block}
