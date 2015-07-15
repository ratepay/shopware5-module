{if $ratepayValidateisDebitSet == 'false'}
    <input id='ratepay_debit_updatedebitdata' type='hidden' value='true'>
    <div class='none'>
        <label for='ratepay_debit_accountholder' class="normal">{s namespace=RatePAY name=accountHolder}Vor- und Nachname Kontoinhaber{/s}
            :</label>
        <input id='ratepay_debit_accountholder' value="{$smarty.session.Shopware.RatePAY.bankdata.bankholder}"
               type='text' class='text'>
    </div>
    <div class='none'>
        <label for='ratepay_debit_accountnumber' class="normal">{s namespace=RatePAY name=accountNumber}Kontonummer / IBAN{/s}:</label>
        <input id='ratepay_debit_accountnumber' value="{$smarty.session.Shopware.RatePAY.bankdata.account}" type='text'
               class='text'>
    </div>
    <div class='none'>
        <label for='ratepay_debit_bankcode' class="normal">{s namespace=RatePAY name=bankCode}Bankleitzahl{/s}:</label>
        <input id='ratepay_debit_bankcode' value="{$smarty.session.Shopware.RatePAY.bankdata.bankcode}" type='text'
               class='text'>
    </div>
    <div class='none'>
        <label for='ratepay_debit_bankname' class="normal">{s namespace=RatePAY name=bankName}Kreditinstitut{/s}:</label>
        <input id='ratepay_debit_bankname' value="{$smarty.session.Shopware.RatePAY.bankdata.bankname}" type='text'
               class='text'>
    </div>

    <script language='javascript'>
        $(document).ready(function () {

            var blzInput       = $(":input#ratepay_debit_bankcode");
            var blzInputLabel  = $("label[for='ratepay_debit_bankcode']");
            var accNumberInput = $(":input#ratepay_debit_accountnumber");

            $(blzInput).prop('disabled', true);
            $(blzInput).hide();
            $(blzInputLabel).hide();

            $(accNumberInput).keyup(function () {
                if ($(this).val().match(/^\d+$/)) {
                    $(blzInput).prop('disabled', false);
                    $(blzInput).show();
                    $(blzInputLabel).show();
                    $(blzInputLabel).text('Bankleitzahl:')
                } else if ($(this).val().match(/at/i)) {
                    $(blzInput).prop('disabled', false);
                    $(blzInput).show();
                    $(blzInputLabel).show();
                    $(blzInputLabel).text('BIC / SWIFT:')
                }
                else {
                    $(blzInput).prop('disabled', true);
                    $(blzInput).hide();
                    $(blzInputLabel).hide();
                }
            })
        });
    </script>
{/if}
