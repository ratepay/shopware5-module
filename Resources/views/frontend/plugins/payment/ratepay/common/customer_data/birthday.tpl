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
                   name="ratepay[customer_data][birthday][day]" maxlength="2"
                   placeholder="{s name="RegisterPlaceholderBirthdayTag"}Tag{/s}"
                   value="{$form_data.ratepay.customer_data.birthday.day}"
                   class="payment--field is--required"
                   required="required" aria-required="true"
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
                    value="{$form_data.ratepay.customer_data.birthday.month}"
                    required="required" aria-required="true"
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
                   value="{$form_data.ratepay.customer_data.birthday.year}"
                   required="required" aria-required="true"
            />
        </div>
        <br style="clear: both">
        <p>
            <small>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um per {$sPayment.description} bezahlen zu können.{/s}</small>
        </p>
    </div>
{/block}
