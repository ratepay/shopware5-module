{if $payment_mean.id == $form_data.payment}
    {if $ratepay.validation.isBirthdayRequired}
        {include file="frontend/plugins/payment/ratepay/common/customer_data/birthday.tpl"}
    {/if}
    {include file="frontend/plugins/payment/ratepay/common/customer_data/phone.tpl"}


    <br style="clear: both"><br/>
    {block name='ratepay_zgb'}
        <div class="register--phone">
            {s namespace=frontend/register/personal_fieldset name=ratepay_zgb_n}
                Es gelten die <a href='https://www.ratepay.com/legal' target='_blank'>zusätzlichen Allgemeinen Geschäftsbedingungen und der Datenschutzhinweis</a> für die Zahlungsart
            {/s} {$sPayment.description}
        </div>
    {/block}
{/if}
