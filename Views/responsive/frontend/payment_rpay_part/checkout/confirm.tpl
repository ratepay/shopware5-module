{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_tos_panel' append}

    {if (
        ( $sPayment.name == 'rpayratepayinvoice')
            ||
        ( $sPayment.name == 'rpayratepaydebit')
            ||
        ( $sPayment.name == 'rpayratepayrate')
    )}

    <div class="ratepay-overlay" style="display: none;">
        <div class="ratepay-modal">
            <p>
                Ihre Zahlungsanfrage wird bearbeitet.
            </p>
        </div>
    </div>


    <div class="content--wrapper">
        <div class="account--change-billing account--content register--content" data-register="true" style="width: 100% !important;">
            <div class="panel has--border is--rounded">
                <div class="account--billing-form">
                    <form name="frmRegister" method="post" action="http://shopware5.dev/account/saveBilling/sTarget/account">
                        <div class="panel register--personal">
                            <h2 class="panel--title is--underline">RatePAY Stammdaten</h2>
                            <div class="panel--body is--wide">

                                {if $sPayment.name == 'rpayratepayinvoice'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                {/if}

                                {if $sPayment.name == 'rpayratepaydebit'}
                                    {include file='frontend/payment_rpay_part/RatePAYSEPAInformationHeader.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYDebitFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYSEPAAGBs.tpl'}
                                {/if}

                                {if $sPayment.name == 'rpayratepayrate'}
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

{* Table actions *}
{block name='frontend_checkout_confirm_confirm_table_actions'}
    <div class="table--actions actions--bottom">
        <div class="main--actions">
            {if !$sLaststock.hideBasket}

                {block name='frontend_checkout_confirm_submit'}
                    {* Submit order button *}
                    {if $sPayment.embediframe || $sPayment.action}
                        <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="false">
                            {s name='ConfirmDoPayment'}Zahlungspflichtig bestellen{/s}<i class="icon--arrow-right"></i>
                        </button>
                    {else}
                        <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="false">
                            {s name='ConfirmActionSubmit'}Zahlungspflichtig bestellen{/s}<i class="icon--arrow-right"></i>
                        </button>
                    {/if}
                {/block}
            {else}
                {block name='frontend_checkout_confirm_stockinfo'}
                    {include file="frontend/_includes/messages.tpl" type="error" content="{s name='ConfirmErrorStock'}{/s}"}
                {/block}
            {/if}
        </div>
    </div>
{/block}
