{extends file="parent:frontend/checkout/confirm.tpl"}
{namespace name="frontend/Ratepay"}

{block name='frontend_checkout_confirm_tos_panel'}
    {$smarty.block.parent}
    {if $ratepay}
        <div class="ratepay-overlay" style="display: none;">
            <div class="ratepay-modal">
                <p>{s name="PleaseWaitWhileRequesting"}{/s}</p>
            </div>
        </div>
        {if $ratepay.sandbox}
            <div class="tos--panel" style="border: 1px dashed black; padding: 5px; background-color: yellow">
                {s name="SanboxIsEnabled"}{/s}
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
                                        {include file='frontend/ratepay/RatePAYErrorMessage.tpl'}
                                        {include file='frontend/ratepay/RatePAYFormElements.tpl'}
                                    {/if}

                                    {if $sPayment.name == 'rpayratepayprepayment'}
                                        {include file='frontend/ratepay/RatePAYErrorMessage.tpl'}
                                        {include file='frontend/ratepay/RatePAYFormElements.tpl'}
                                    {/if}

                                    {if $sPayment.name == 'rpayratepaydebit'}
                                        {include file='frontend/ratepay/RatePAYSEPAInformationHeader.tpl'}
                                        {include file='frontend/ratepay/RatePAYErrorMessage.tpl'}
                                        {include file='frontend/ratepay/RatePAYFormElements.tpl'}
                                        {include file='frontend/ratepay/RatePAYDebitFormElements.tpl'}
                                        {include file='frontend/ratepay/RatePAYSEPAAGBs.tpl'}
                                    {/if}

                                    {if $sPayment.name == 'rpayratepayrate' || $sPayment.name == 'rpayratepayrate0'}
                                        {include file='frontend/ratepay/RatePAYErrorMessage.tpl'}
                                        {include file='frontend/ratepay/RatePAYFormElements.tpl'}
                                        {include file='frontend/ratepay/RatePAYRatenrechner.tpl'}
                                        <div id="debitDetails">
                                            {include file='frontend/ratepay/RatePAYSEPAInformationHeader.tpl'}
                                            {include file='frontend/ratepay/RatePAYDebitFormElements.tpl'}
                                            {include file='frontend/ratepay/RatePAYSEPAAGBs.tpl'}
                                        </div>
                                        <div id="switchInformation" style="display:none;font-weight:bold;">
                                            Die Ratenberechnung wurde aufgrund der geänderten Zahlungsweise angepasst
                                        </div>
                                        <a id="changeFirstday" style="display: none;cursor: pointer;" onclick="changeFirstday(28);">Ich möchte die Ratenzahlung selbst vornehmen und nicht per Lastschrift begleichen</a>
                                        <a id="changeFirstday2" style="display: none;cursor: pointer;" onclick="changeFirstday(2);">Ich möchte die Ratenzahlung per Lastschrift begleichen</a>
                                    {/if}
                                    <br />
                                    <small><strong>{s namespace=frontend/register/personal_fieldset name=RegisterPersonalRequiredText}* hierbei handelt es sich um ein Pflichtfeld{/s}</strong></small>
                                </div>
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
