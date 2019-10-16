{* Accountholder *}
{block name='ratepay_frontend_accountholder'}
    <div class="register--accountholder">
        <label for="" class="birthday--label">{s namespace="backend/order/main" name="debit/account_holder"}Kontoinhaber{/s}:</label>
        <input class="register--field disabled"
               type="text"
               disabled="disabled"
               value="{$form_data.ratepay.bank_account.account_holder}"
        />
    </div>
{/block}

{* Accountnumber/ IBAN *}
{block name='ratepay_frontend_accountnumber'}
    <div class="register--accountnumber">
        <label for="ratepay_debit_accountnumber" class="birthday--label">{s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}IBAN / Kontonummer{/s}*</label>
        <input id="ratepay_debit_accountnumber"
               name="ratepay[bank_account][iban]"
               class="register--field is--required"
               type="text"
               required="required"
               aria-required="true"
               placeholder="{s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}IBAN / Kontonummer{/s}"
               value="{$form_data.ratepay.bank_account.iban}"
        />
    </div>
{/block}

{* Bankcode *}
{block name='ratepay_frontend_bankcode'}
    <div class="register--accountnumber ratepay_debit_bankcode">
        <label for="ratepay_debit_accountnumber" class="birthday--label">{s namespace="frontend/plugins/payment/debit" name="PaymentDebitPlaceholderBankcode"}Bankleitzahl{/s}*</label>
        <input id="ratepay_debit_bankcode"
               name="ratepay[bank_account][bankCode]"
               class="register--field is--required"
               type="text"
               {*}required="required"
               aria-required="true"{*}
               placeholder="{s namespace="frontend/plugins/payment/debit" name="PaymentDebitPlaceholderBankcode"}Bankleitzahl{/s}*"
               value="{$form_data.ratepay.bank_account.bankCode}"
        />
    </div>
{/block}





