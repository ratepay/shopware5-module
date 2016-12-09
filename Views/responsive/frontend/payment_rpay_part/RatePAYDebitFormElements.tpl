{* Hidden Data *}
{block name='ratepay_frontend_updatedebitdata'}
    <input id="ratepay_debit_updatedebitdata" name="ratepay_debit_updatedebitdata" type='hidden' value='true'>
{/block}

{* Accountholder *}
{block name='ratepay_frontend_accountholder'}
    <div class="register--accountholder">
        <label for="" class="birthday--label">{s namespace=RatePAY name=accountholder}Kontoinhaber{/s}:</label>
        <input class="register--field disabled" type="text" disabled="true" value="{$sUserData.billingaddress.firstname} {$sUserData.billingaddress.lastname}">
    </div>
{/block}

{* Accountnumber/ IBAN *}
{block name='ratepay_frontend_accountnumber'}
    <div class="register--accountnumber">
        <label for="ratepay_debit_accountnumber" class="birthday--label">{s namespace=RatePAY name=accountNumber}IBAN / Kontonummer{/s}*</label>
        <input id="ratepay_debit_accountnumber" name="ratepay_debit_accountnumber" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=accountNumber}IBAN / Kontonummer{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.account}{$smarty.session.Shopware.RatePAY.bankdata.account|escape}{/if}">
    </div>
{/block}

{* Bankcode/ BIC *}
{block name='ratepay_frontend_bankcode'}
    <div class="register--accountnumber ratepay_debit_bankcode">
        <label for="ratepay_debit_accountnumber" class="birthday--label">{s namespace=RatePAY name=bankCode}BIC / Bankleitzahl{/s}*</label>
        <input id="ratepay_debit_bankcode" name="ratepay_debit_bankcode" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=bankCode}BIC / Bankleitzahl{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.account}{$smarty.session.Shopware.RatePAY.bankdata.bankcode|escape}{/if}">
    </div>
{/block}





