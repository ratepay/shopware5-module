{* Hidden Data *}
{block name='ratepay_frontend_updatedebitdata'}
    <input id='ratepay_debit_updatedebitdata' type='hidden' value='true'>
{/block}

{* Accountholder *}
{block name='ratepay_frontend_accountholder'}
    <div class="register--accountholder">
        <input id="ratepay_debit_accountholder" name="ratepay_debit_accountholder" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=accountHolder}Vor- und Nachname Kontoinhaber{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.bankholder}{$smarty.session.Shopware.RatePAY.bankdata.bankholder|escape}{/if}">
    </div>
{/block}

{* Accountnumber/ IBAN *}
{block name='ratepay_frontend_accountnumber'}
    <div class="register--accountnumber">
        <input id="ratepay_debit_accountnumber" name="ratepay_debit_accountnumber" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=accountNumber}Kontonummer / IBAN{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.account}{$smarty.session.Shopware.RatePAY.bankdata.account|escape}{/if}">
    </div>
{/block}


{* Bankcode/ BIC *}
{block name='ratepay_frontend_bankcode'}
    <div class="register--accountnumber">
        <input id="ratepay_debit_bankcode" name="ratepay_debit_accountnumber" class="register--field is--required" type="text" required="required" aria-required="true" placeholder="{s namespace=RatePAY name=bankCode}Bankleitzahl / BIC{/s}*" value="{if $smarty.session.Shopware.RatePAY.bankdata.account}{$smarty.session.Shopware.RatePAY.bankdata.bankcode|escape}{/if}">
    </div>
{/block}





