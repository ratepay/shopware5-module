{* Hidden Data *}
{block name='ratepay_frontend_updatedebitdata'}
    <input id="ratepay_debit_updatedebitdata" name="ratepay_debit_updatedebitdata" type='hidden' value='true'>
{/block}

{* Accountholder *}
{block name='ratepay_frontend_accountholder'}
    <div class="register--accountholder">
        <label for="" class="birthday--label">{s namespace=backend/order/main name=debit/account_holder}Kontoinhaber{/s}:</label>
        <input class="register--field disabled" type="text" disabled="true" value="{$sUserData.billingaddress.firstname} {$sUserData.billingaddress.lastname}">
    </div>
{/block}

{* Accountnumber/ IBAN *}
{block name='ratepay_frontend_accountnumber'}
    <div class="register--accountnumber">
        <label for="ratepay_debit_accountnumber" class="birthday--label">{s namespace=frontend/plugins/payment/sepa name=PaymentSepaLabelIban}IBAN / Kontonummer{/s}*</label>
        <input id="ratepay_debit_accountnumber" name="ratepay_debit_accountnumber" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=accountNumber}IBAN / Kontonummer{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.account}{$smarty.session.Shopware.RatePAY.bankdata.account|escape}{/if}">
    </div>
{/block}





