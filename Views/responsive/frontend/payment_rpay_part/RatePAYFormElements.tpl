<div class="ratepay-overlay" style="display: none;">
    <div class="ratepay-modal">
        <p>
            Ihre Zahlungsanfrage wird bearbeitet.
        </p>
    </div>
</div>


<div class="content--wrapper">
    <div class="account--change-billing account--content register--content" data-register="true">
        <div class="panel has--border is--rounded">
            <div class="account--billing-form">
                <form name="frmRegister" method="post" action="http://shopware5.dev/account/saveBilling/sTarget/account">
                    <div class="panel register--personal">
                        <h2 class="panel--title is--underline">RatePAY Stammdaten</h2>
                        <div class="panel--body is--wide">



                            {if $ratepayValidateIsB2B == 'true'}
                                <div class="none">
                                    <label for="ratepay_ustid" class="normal">{s namespace=RatePAY name=vatId}Umsatzsteuer{/s}:</label>
                                    <input id="ratepay_ustid" class="text" type="text" value="{if $sUserData.billingaddress.ustid}{$sUserData.billingaddress.ustid}{/if}">
                                </div>
                                <div class="none">
                                    <label for="ratepay_company" class="normal">{s namespace=RatePAY name=company}Firmenname{/s}:</label>
                                    <input id="ratepay_company" class="text" type="text" value="{if $sUserData.billingaddress.company}{$sUserData.billingaddress.company}{/if}">
                                </div>
                            {/if}


                            {* Birthday *}
                            {block name='ratepay_frontend_birthday'}
                                <div class="register--birthdate">
                                    <label for="register_personal_birthdate" class="birthday--label">{s namespace=RatePAY name=birthday}Geburtsdatum{/s}*</label>
                                    <div class="register--birthday field--select">
                                        <select id="ratepay_birthday" name="ratepay[personal][birthday]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if} class="{if {config name=requireBirthdayField}}is--required{/if}{if $error_flags.birthday && {config name=requireBirthdayField}} has--error{/if}">
                                            <option value="">{s namespace=RatePAY name=dob_day}Tag{/s}</option>
                                            {section name="birthdate" start=1 loop=32 step=1}
                                                <option value="{if $smarty.section.birthdate.index < 10}0{$smarty.section.birthdate.index}{else}{$smarty.section.birthdate.index}{/if}"
                                                        {if $smarty.section.birthdate.index eq $sUserData.billingaddress.birthday|date_format:"%e"}selected{/if}>{$smarty.section.birthdate.index}</option>
                                            {/section}
                                        </select>
                                    </div>

                                    <div class="register--birthmonth field--select">
                                        <select id="ratepay_birthmonth" name="ratepay[personal][birthmonth]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if} class="{if {config name=requireBirthdayField}}is--required{/if}{if $error_flags.birthmonth && {config name=requireBirthdayField}} has--error{/if}">
                                            <option value="">{s namespace=RatePAY name=dob_month}Monat{/s}</option>
                                            {section name="birthmonth" start=1 loop=13 step=1}
                                                <option value="{if $smarty.section.birthmonth.index < 10}0{$smarty.section.birthmonth.index}{else}{$smarty.section.birthmonth.index}{/if}"
                                                        {if $smarty.section.birthmonth.index eq $sUserData.billingaddress.birthday|date_format:"%m"}selected{/if}>{$smarty.section.birthmonth.index}</option>
                                            {/section}
                                        </select>
                                    </div>

                                    <div class="register--birthyear field--select">
                                        <select id="ratepay_birthyear" name="ratepay[personal][birthyear]"{if {config name=requireBirthdayField}} required="required" aria-required="true"{/if} class="{if {config name=requireBirthdayField}}is--required{/if}{if $error_flags.birthyear && {config name=requireBirthdayField}} has--error{/if}">
                                            <option value="">{s namespace=RatePAY name=dob_year}Jahr{/s}</option>
                                            {section name="birthyear" start=$smarty.now|date_format:"%Y"-18 loop=2000 max=100 step=-1}
                                                <option value="{$smarty.section.birthyear.index}"
                                                        {if $smarty.section.birthyear.index eq $sUserData.billingaddress.birthday|date_format:"%Y"}selected{/if}>{$smarty.section.birthyear.index}</option>
                                            {/section}
                                        </select>
                                    </div>

                                    <p>{s namespace=RatePAY name=dob_info}Sie müssen mindestens 18 Jahre alt sein, um mit RatePay bezahlen zu können.{/s}</p>

                                </div>
                            {/block}

                            {* Phone *}
                            {block name='ratepay_frontend_phone'}
                            <div class="register--phone">
                                <label for="register_personal_birthdate" class="birthday--label">{s namespace=RatePAY name=phone}Telefonnummer{/s}*</label>
                                <input id="ratepay_phone" class="text" type="text" class="register--field is--required" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=phone}Telefonnummer{/s}*" value="{if $sUserData.billingaddress.phone}{$sUserData.billingaddress.phone|escape}{/if}">
                            </div>
                            {/block}

                        </div>
                    </div>

                    <div class="register--required-info required_fields">
                        * hierbei handelt es sich um ein Pflichtfeld
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>





