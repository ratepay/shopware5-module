{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_tos_panel' append}

    {if (
        ( $sPayment.name == 'rpayratepayinvoice')
            ||
        ( $sPayment.name == 'rpayratepaydebit')
            ||
        ( $sPayment.name == 'rpayratepayrate')
        ||
        ( $sPayment.name == 'rpayratepayrate0')
    )}

    <div class="ratepay-overlay" style="display: none;">
        <div class="ratepay-modal">
            <p>
                Ihre Zahlungsanfrage wird bearbeitet.
            </p>
        </div>
    </div>
    {if ($ratepaySandbox === true)}
        <div class="tos--panel" style="border: 1px dashed black; padding: 5px; background-color: yellow">
            Testmodus aktiv, bitte nutzen Sie diese Zahlungsart nicht für die Bestellung und informieren Sie den Händler über diese Nachricht
        </div>
    {/if}
    <div class="content--wrapper">
        <div data-register="true" style="width: 100% !important;">
            <div class="panel has--border is--rounded">
                <div class="account--billing-form">
                    <form name="frmRegister" method="post" action="http://shopware5.dev/account/saveBilling/sTarget/account">
                        <div class="panel register--personal">
                            <h2 class="panel--title is--underline">{$sPayment.description}</h2>
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

                                {if $sPayment.name == 'rpayratepayrate' || $sPayment.name == 'rpayratepayrate0'}
                                    {include file='frontend/payment_rpay_part/RatePAYErrorMessage.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYFormElements.tpl'}
                                    {include file='frontend/payment_rpay_part/RatePAYRatenrechner.tpl'}
                                    <div id="debitDetails">
                                        {include file='frontend/payment_rpay_part/RatePAYSEPAInformationHeader.tpl'}
                                        {include file='frontend/payment_rpay_part/RatePAYDebitFormElements.tpl'}
                                        {include file='frontend/payment_rpay_part/RatePAYSEPAAGBs.tpl'}
                                    </div>
                                    <div id="switchInformation" style="display:none;font-weight:bold;">
                                        Die Ratenberechnung wurde aufgrund der geänderten Zahlungsweise angepasst
                                    </div>
                                    <a id="changeFirstday" style="display: none;cursor: pointer;" onclick="changeFirstday(28);">Ich möchte die Ratenzahlung selbst vornehmen und nicht per Lastschrift begleichen</a>
                                    <a id="changeFirstday2" style="display: none;cursor: pointer;" onclick="changeFirstday(2);">Ich möchte die Ratenzahlung per Lastschrift begleichen</a>
                                {/if}

                            </div>
                        </div>

                        <div class="register--required-info required_fields">
                            <strong>{s namespace=frontend/register/personal_fieldset name=RegisterPersonalRequiredText}* hierbei handelt es sich um ein Pflichtfeld{/s}</strong>
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
                            {s namespace=frontend/checkout/confirm name=ConfirmActionSubmit}Zahlungspflichtig bestellen{/s}<i class="icon--arrow-right"></i>
                        </button>
                    {else}
                        <button type="submit" class="btn is--primary is--large right is--icon-right" form="confirm--form" data-preloader-button="false">
                            {s namespace=frontend/checkout/confirm name=ConfirmActionSubmit}Zahlungspflichtig bestellen{/s}<i class="icon--arrow-right"></i>
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
