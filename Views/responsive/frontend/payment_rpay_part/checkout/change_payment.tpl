{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_fieldset_description' append}

    {if (
        ($sUserData.additional.payment.name == 'rpayratepayinvoice' && $payment_mean.name == 'rpayratepayinvoice')
            ||
        ($sUserData.additional.payment.name == 'rpayratepaydebit' && $payment_mean.name == 'rpayratepaydebit')
            ||
        ($sUserData.additional.payment.name == 'rpayratepayrate' && $payment_mean.name == 'rpayratepayrate')
    )}

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

                                {if $sUserData.additional.payment.name == 'rpayratepayinvoice' && $payment_mean.name == 'rpayratepayinvoice'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                {/if}

                                {if $sUserData.additional.payment.name == 'rpayratepaydebit' && $payment_mean.name == 'rpayratepaydebit'}
                                    {include file='frontend/payment_rpay_part/RatePAYSEPAInformationHeader.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYDebitFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYSEPAAGBs.tpl'}
                                {/if}

                                {if $sUserData.additional.payment.name == 'rpayratepayrate' && $payment_mean.name == 'rpayratepayrate'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYRatenrechner.tpl'}
                                {/if}

                            </div>
                        </div>

                        <div class="register--required-info required_fields">
                            <strong>* hierbei handelt es sich um ein Pflichtfeld</strong>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {/if}
{/block}
