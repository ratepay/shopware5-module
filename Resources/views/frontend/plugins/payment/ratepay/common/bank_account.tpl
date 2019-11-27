{block name='ratepay_frontend_accountholder'}
    <div class="form-group row bank-account-input">
        <label class="col-sm-2 col-form-label">
            {s namespace="frontend/plugins/payment/sepaemail" name="SepaEmailName"}{/s}:
        </label>
        <div class="col-sm-10">
            <input class="register--field disabled"
               type="text"
               disabled="disabled"
               value="{$form_data.ratepay.bank_account.account_holder}"
           />
        </div>
    </div>
{/block}

{block name='ratepay_frontend_accountnumber'}
    <!-- Account number is only allowed for customers with german billing address. IBAN must be used for all others -->
    <div class="form-group row bank-account-input">
        <label class="col-sm-2 col-form-label" for="rp-iban-account-number">
            {s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}{/s}
        </label>
        <div class="col-sm-10">
            <input id="rp-iban-account-number"
               name="ratepay[bank_account][iban]"
               class="register--field is--required"
               type="text"
               required="required"
               aria-required="true"
               placeholder="{s namespace="frontend/plugins/payment/sepa" name="PaymentSepaLabelIban"}{/s}"
               value="{$form_data.ratepay.bank_account.iban}"
            />
        </div>
    </div>
{/block}

{block name='ratepay_frontend_bankcode'}
    <!-- Bank code is only necesarry if account number (no iban) is set -->
    <div class="form-group row bank-account-input" id="rp-form-bank-code">
        <label class="col-sm-2 col-form-label" for="rp-bank-code">
            {s namespace="frontend/plugins/payment/debit" name="PaymentDebitPlaceholderBankcode"}{/s}
        </label>
        <div class="col-sm-10">
            <input id="rp-bank-code"
               name="ratepay[bank_account][bankCode]"
               class="register--field is--required"
               type="text"
               placeholder="{s namespace="frontend/plugins/payment/debit" name="PaymentDebitPlaceholderBankcode"}Bankleitzahl{/s}*"
               value="{$form_data.ratepay.bank_account.bankCode}"
            />
        </div>
    </div>
{/block}




