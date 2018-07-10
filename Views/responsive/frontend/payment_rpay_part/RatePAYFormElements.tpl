<script type="text/javascript">
    ratepayPaymentMethodSelected = true;
</script>
{if $ratepayValidateIsB2B == 'false'}
    {* Birthday *}
    {block name='ratepay_frontend_birthday'}
        <div class="register--birthdate">
            <div class="rp-birthday field--select" id="birthday">
                <label for="register_personal_birthdate" class="birthday--label">{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthday}Geburtsdatum{/s}*</label>
                <br />
                <input type="text"
                    name="ratepay[personal][birthday]"
                    id="ratepay_birthday"
                    placeholder="Geburtsdatum*"
                    data-datepicker="true"
                    data-allowInput="true"
                    data-maxDate="01.01.{$smarty.now|date_format:"%Y" - 18}"
                    value="{if $sUserData.billingaddress.birthday}{$sUserData.billingaddress.birthday}{/if}{if $sUserData.additional.user.birthday}{$sUserData.additional.user.birthday}{/if}" />
            </div>
            <br style="clear: both">
            <p><small>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um per {$sPayment.description} bezahlen zu können.{/s}</small></p>
        </div>
    {/block}
{/if}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="rp-birthday field--select">
        <label for="ratepay_phone" class="birthday--label">{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s}* </label>
        <br />
        <input id="ratepay_phone" name="ratepay_phone" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s}*" value="{if $ratepayPhone}{$ratepayPhone}{else}{$sUserData.billingaddress.phone|escape}{/if}">
    </div>
{/block}
<br style="clear: both"><br/>
{block name='ratepay_zgb'}
    <div class="register--phone">
        {s namespace=frontend/register/personal_fieldset name=ratepay_zgb_n}
            Es gelten die <a href='https://www.ratepay.com/zgb-dse' target='_blank'>zusätzlichen Allgemeinen Geschäftsbedingungen und der Datenschutzhinweis</a> für die Zahlungsart
        {/s} {$sPayment.description}
    </div>
{/block}
