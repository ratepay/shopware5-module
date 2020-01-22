<script type="text/javascript">
    ratepayPaymentMethodSelected = true;
</script>
{if $ratepayValidateIsB2B == 'false'}
    {* Birthday *}
    {block name='ratepay_frontend_birthday'}
        <div class="register--birthdate">
            <label for="register_personal_birthdate" class="birthday--label">
                <strong>{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthday}Geburtsdatum{/s}</strong>
            </label>
            <br />

            <div class="rp-birthday field--select">
                <label for="register_personal_birthdate" class="birthday--label">
                    {s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthdayTag}Tag{/s}*
                </label>
                <br />
                <input
                    type="text"
                    id="ratepay_birthday"
                    name="ratepay[personal][birthday]"
                    maxlength="2"
                    placeholder="Tag"
                    required="required"
                    aria-required="true"
                    data-rp-local-storage="true"
                    value="{if $sUserData.billingaddress.birthday}{$sUserData.billingaddress.birthday|date_format:'%d'}{/if}{if $sUserData.additional.user.birthday}{$sUserData.additional.user.birthday|date_format:'%d'}{/if}"/>
            </div>

            <div class="rp-birthmonth field--select">
                <label for="register_personal_birthdate" class="birthday--label">
                    {s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthdayMonth}Monat{/s}*
                </label>
                <br />
                <input
                    type="text"
                    id="ratepay_birthmonth"
                    name="ratepay[personal][birthmonth]"
                    maxlength="2"
                    placeholder="Monat"
                    required="required"
                    aria-required="true"
                    data-rp-local-storage="true"
                    value="{if $sUserData.billingaddress.birthday}{$sUserData.billingaddress.birthday|date_format:'%m'}{/if}{if $sUserData.additional.user.birthday}{$sUserData.additional.user.birthday|date_format:'%m'}{/if}"/>
            </div>

            <div class="rp-birthyear field--select">
                <label for="register_personal_birthdate" class="birthday--label">
                    {s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthdayYear}Jahr{/s}*
                </label>
                <br />
                <input
                    type="text"
                    id="ratepay_birthyear"
                    name="ratepay[personal][birthyear]"
                    maxlength="4"
                    placeholder="Jahr"
                    required="required"
                    aria-required="true"
                    data-rp-local-storage="true"
                    value="{if $sUserData.billingaddress.birthday}{$sUserData.billingaddress.birthday|date_format:'%Y'}{/if}{if $sUserData.additional.user.birthday}{$sUserData.additional.user.birthday|date_format:'%Y'}{/if}"/>
            </div>
            <br style="clear: both">
            <p><small>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um per {$sPayment.description} bezahlen zu können.{/s}</small></p>
        </div>
    {/block}
{/if}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="rp-birthday field--select">
        <label for="ratepay_phone" class="birthday--label">{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s} </label>
        <br />
        <input id="ratepay_phone"
               name="ratepay_phone"
               class="register--field is--required"
               type="text"
               placeholder="{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s}"
               data-rp-local-storage="true"
               value="{if $ratepayPhone}{$ratepayPhone}{else}{$sUserData.billingaddress.phone|escape}{/if}"
        >
    </div>
{/block}
<br style="clear: both"><br/>
{block name='ratepay_zgb'}
    <div class="register--phone">
        {s namespace=frontend/register/personal_fieldset name=ratepay_zgb_n}
            Es gelten die <a href='https://www.ratepay.com/legal' target='_blank'>zusätzlichen Allgemeinen Geschäftsbedingungen und der Datenschutzhinweis</a> für die Zahlungsart
        {/s} {$sPayment.description}
    </div>
{/block}
