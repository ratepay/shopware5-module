<script type="text/javascript">
    ratepayPaymentMethodSelected = true;
</script>
{if $ratepayValidateIsB2B == 'false'}
    {* Birthday *}
    {block name='ratepay_frontend_birthday'}
        <div class="register--birthdate">
            <label for="register_personal_birthdate" class="birthday--label">{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderBirthday}Geburtsdatum{/s}*</label>
            <br/>
            <div class="rp-birthday field--select" id="foo">
                <select id="ratepay_birthday" name="ratepay[personal][birthday]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if}>
                    <option value="">{s namespace=frontend/register/personal_fieldset name=RegisterBirthdaySelectDay}Tag{/s}</option>
                    {section name="birthdate" start=1 loop=32 step=1}
                        <option value="{if $smarty.section.birthdate.index < 10}0{$smarty.section.birthdate.index}{else}{$smarty.section.birthdate.index}{/if}"
                                {if $smarty.section.birthdate.index eq $sUserData.billingaddress.birthday|date_format:"%e" or $smarty.section.birthdate.index eq $sUserData.additional.user.birthday|date_format:"%e"}selected{/if}>{$smarty.section.birthdate.index}</option>
                    {/section}
                </select>
            </div>

            <div class="rp-birthmonth field--select">
                <select id="ratepay_birthmonth" name="ratepay[personal][birthmonth]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if}>
                    <option value="">{s namespace=frontend/register/personal_fieldset name=RegisterBirthdaySelectMonth}Monat{/s}</option>
                    {section name="birthmonth" start=1 loop=13 step=1}
                        <option value="{if $smarty.section.birthmonth.index < 10}0{$smarty.section.birthmonth.index}{else}{$smarty.section.birthmonth.index}{/if}"
                                {if $smarty.section.birthmonth.index eq $sUserData.billingaddress.birthday|date_format:"%m" or $smarty.section.birthmonth.index eq $sUserData.additional.user.birthday|date_format:"%m"}selected{/if}>{$smarty.section.birthmonth.index}</option>
                    {/section}
                </select>
            </div>

            <div class="rp-birthyear field--select">
                <select id="ratepay_birthyear" name="ratepay[personal][birthyear]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if}>
                    <option value="">{s namespace=frontend/register/personal_fieldset name=RegisterBirthdaySelectYear}Jahr{/s}</option>
                    {section name="birthyear" start=$smarty.now|date_format:"%Y"-18 loop=2000 max=100 step=-1}
                        <option value="{$smarty.section.birthyear.index}"
                                {if $smarty.section.birthyear.index eq $sUserData.billingaddress.birthday|date_format:"%Y" or $smarty.section.birthyear.index eq $sUserData.additional.user.birthday|date_format:"%Y"}selected{/if}>{$smarty.section.birthyear.index}</option>
                    {/section}
                </select>
            </div>
            <br style="clear: both"><br>
            <p>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um mit RatePAY bezahlen zu können.{/s}</p>
        </div>
    {/block}
{/if}

{* Phone *}
{block name='ratepay_frontend_phone'}
    <div class="register--phone">
        <label for="ratepay_phone" class="birthday--label">{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s}* </label>
        <input id="ratepay_phone" name="ratepay_phone" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=frontend/register/personal_fieldset name=RegisterPlaceholderPhone}Telefonnummer{/s}*" value="{if $ratepayPhone}{$ratepayPhone}{else}{$sUserData.billingaddress.phone|escape}{/if}">
    </div>
{/block}
<br/><br/>
{block name='ratepay_zgb'}
    <div class="register--phone">
        {s namespace=frontend/register/personal_fieldset name=ratepay_zgb}
            Es gelten die <a href='https://www.ratepay.com/zgb-dse' target='_blank'>zusätzlichen Geschäftsbedingungen und der Datenschutzhinweis</a> der RatePAY GmbH
        {/s}
    </div>
{/block}
