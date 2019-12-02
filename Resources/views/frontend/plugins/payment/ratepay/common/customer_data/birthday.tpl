{namespace name="frontend/register/personal_fieldset"}
{* Birthday *}
<input type="hidden" name="ratepay[customer_data][birthday_required]" value="{if $form_data.ratepay.customer_data.birthday_required}1{else}0{/if}">
{if $form_data.ratepay.customer_data.birthday_required != false}
    {block name='ratepay_frontend_birthday'}
        <div class="form-group row birthday-input-group">
            <label class="col-sm-2 col-form-label">
                {s name="RegisterPlaceholderBirthday"}{/s}
            </label>
            <div class="col-sm-10 row">
                <div class="col-xxs-12 col-xs-4 col-sm-3 col-md-2">
                    <div class="row row-no-gutters">
                        <div class="col-xxs-4 col-xs-12">
                            <label for="register_personal_birthdate" class="birthday--label">{s name="RegisterBirthdaySelectDay"}Tag{/s}*</label>
                        </div>
                        <div class="col-xxs-8 col-xs-12">
                            <input type="text"
                                   id="ratepay_birthday"
                                   name="ratepay[customer_data][birthday][day]" maxlength="2"
                                   placeholder="{s name="RegisterBirthdaySelectDay"}{/s}"
                                   value="{$form_data.ratepay.customer_data.birthday.day}"
                                   class="payment--field is--required"
                                   required="required" aria-required="true"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-xxs-12 col-xs-4 col-sm-3 col-md-2">
                    <div class="row row-no-gutters">
                        <div class="col-xxs-4 col-xs-12">
                            <label for="register_personal_birthdate" class="birthday--label">{s name="RegisterBirthdaySelectMonth"}{/s}*</label>
                        </div>
                        <div class="col-xxs-8 col-xs-12">
                            <input type="text"
                                   id="ratepay_birthmonth"
                                   name="ratepay[customer_data][birthday][month]"
                                   maxlength="2"
                                   placeholder="{s name="RegisterBirthdaySelectMonth"}{/s}"
                                   value="{$form_data.ratepay.customer_data.birthday.month}"
                                   required="required" aria-required="true"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-xxs-12 col-xs-4 col-sm-3 col-md-2">
                    <div class="row row-no-gutters">
                        <div class="col-xxs-4 col-xs-12">
                            <label for="register_personal_birthdate" class="birthday--label">{s name="RegisterBirthdaySelectYear"}{/s}*</label>
                        </div>
                        <div class="col-xxs-8 col-xs-12">
                            <input type="text"
                                   id="ratepay_birthyear"
                                   name="ratepay[customer_data][birthday][year]"
                                   maxlength="4"
                                   placeholder="{s name="RegisterBirthdaySelectYear"}Jahr{/s}"
                                   value="{$form_data.ratepay.customer_data.birthday.year}"
                                   required="required" aria-required="true"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <small>{s namespace="frontend/ratepay/fields" name="BirthdayInfo"}{/s}</small>
                </div>
            </div>
        </div>
    {/block}
{/if}
