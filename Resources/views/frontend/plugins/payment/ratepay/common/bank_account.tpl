{block name="ratepay_payment_method__bank_account"}

    {block name="ratepay_payment_method__bank_account__legal_text"}
        <div class="legal-text">
            <p>{s namespace="frontend/plugins/payment/ratepay" name="SepaMandatInformation"}{/s}</p>
        </div>
    {/block}

    {block name='ratepay_payment_method__bank_account_accountholder'}
        <div class="ratepay-input">
            <label for="ratepay-bankdata-accountHolder">{s namespace="frontend/plugins/payment/ratepay" name="SepaName"}{/s}:</label>
            <div class="ratepay-input_value bank-account-input">
                <select id="ratepay-bankdata-accountHolder" class="register--field disabled"
                        name="ratepay[bank_account][accountHolder][selected]"
                        required="required"
                        aria-required="true">
                    {foreach from=$sFormData.ratepay.bank_account.accountHolder.list item=accountHolder}
                        <option value="{$accountHolder}"{if $sFormData.ratepay.bank_account.accountHolder.selected == $accountHolder} selected="selected"{/if}>{$accountHolder}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/block}

    {block name="ratepay_payment_method__bank_account__iban"}
        <div class="ratepay-input">
            <label for="ratepay-bankdata-iban">{s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}{/s}</label>
            <div class="ratepay-input_value">
                <input id="ratepay-bankdata-iban"
                       name="ratepay[bank_account][iban]"
                       class="register--field is--required"
                       type="text"
                       required="required"
                       aria-required="true"
                       placeholder="{s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}{/s}"
                       value="{$sFormData.ratepay.bank_account.iban}"
                />
            </div>
        </div>
    {/block}

    {block name="ratepay_payment_method__bank_account__agreement"}
        <div class="ratepay-input">
            <label></label>
            <div class="ratepay-input_value checkbox-input">
                <input type="checkbox" name="ratepay[sepa_agreement]" id="rp-sepa-aggreement" required="required" />
                <label for="rp-sepa-aggreement" class="rp-label-sepa-agreement">{s namespace="frontend/plugins/payment/ratepay" name="sepaAuthorize"}{/s}</label>
            </div>
        </div>
    {/block}

{/block}


