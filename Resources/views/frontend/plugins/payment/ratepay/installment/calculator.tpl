<!--
  ~ Copyright (c) Ratepay GmbH
  ~
  ~ For the full copyright and license information, please view the LICENSE
  ~ file that was distributed with this source code.
  -->

<div class="payment-method-installment-content">
    {block name="ratepay_payment_method__ic__plan_preview_outer"}
        <div class="ratepay-installment-preview" id="rpResultPreviewContainer"></div>
    {/block}

    {block name="ratepay_payment_method__ic_modal"}
        <div class="installment-calculator__modal is--hidden">
            <div class="installment-calculator__modal-inner">
                {block name="ratepay_payment_method__ic_modal_header"}
                    <div class="installment-calculator__modal-header">
                        <div data-trigger="close" class="close">
                            <i class="icon--cross"></i>
                        </div>
                    </div>
                {/block}


                {block name="ratepay_payment_method__ic"}
                    <div class="installment-calculator">
                        <div class="container">
                            {block name="ratepay_payment_method__ic__inputs_outer"}
                                {block name="ratepay_payment_method__ic__intro"}
                                    <div class="calculator-intro">
                                        {$ratepay.translations.rp_calculation_intro_part1} {$ratepay.translations.rp_calculation_intro_part2} {$ratepay.translations.rp_calculation_intro_part3}
                                    </div>
                                {/block}

                                {block name="ratepay_payment_method__ic__calculator"}
                                    <div class="calculator_calculation-types">
                                        {block name="ratepay_payment_method__ic__calculator_runtime"}
                                            <div class="calculator_calculation-type">
                                                <div class="cct-heading">
                                                    <h2>{$ratepay.translations.rp_runtime_title}</h2>
                                                    {$ratepay.translations.rp_runtime_description}
                                                </div>

                                                <div class="cct-body">
                                                    <div class="form-group runtime-select-container">
                                                        <select id="rp-btn-runtime">
                                                            {foreach from=$ratepay.calculator.rp_allowedMonths item=month}
                                                                <option value="{$month}">{$month} {$ratepay.translations.rp_monthly_installment_pl}</option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        {/block}

                                        {block name="ratepay_payment_method__ic__calculator_ratevalue"}
                                            <div class="calculator_calculation-type">
                                                <div class="cct-heading">
                                                    <h2>{$ratepay.translations.rp_rate_title}</h2>
                                                    {$ratepay.translations.rp_rate_description}
                                                </div>

                                                <div class="cct-body">
                                                    <div class="cct_by-rate">
                                                        <div class="input-group">
                                                            <span class="currency">&euro;</span>
                                                            <input type="text" id="rp-rate-value" class="form-control cct_by-rate_input" />
                                                        </div>
                                                        <button class="btn btn-default rp-btn-rate" type="button" disabled>{$ratepay.translations.rp_calculate_rate}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        {/block}
                                    </div>
                                {/block}
                            {/block}

                            {block name="ratepay_payment_method__ic__plan_outer"}
                                <div id="rpResultContainer"></div>
                            {/block}
                        </div>
                    </div>

                    <div id="ratepay__installment__message-template" style="display: none!important">
                        {include file="frontend/_includes/messages.tpl" type="error" content="<div class='placeholder'></div>" isHidden=true}
                    </div>
                {/block}
            </div>
        </div>
    {/block}

    {block name="ratepay_payment_method__ic__payment"}
        <div class="installment-payment is--hidden">
            {block name="ratepay_payment_method__ic__payment_type_outer"}
                <div class="payment-type-select-container">
                    {block name="ratepay_payment_method__ic__payment_type_links"}
                        <div class="rp-payment-type-switch" id="rp-switch-payment-type-bank-transfer">
                            <span>{$ratepay.translations.rp_switch_payment_type_bank_transfer}</span>
                        </div>
                        <div class="rp-payment-type-switch" id="rp-switch-payment-type-direct-debit">
                            <span>{$ratepay.translations.rp_switch_payment_type_direct_debit}</span>
                        </div>
                    {/block}
                </div>
            {/block}
            {block name="ratepay_payment_method__ic__sepa_outer"}
                <div id="ratepay-installment_bank-data" class="ratepay-installment_bank-data_outer">
                    {block name="ratepay_payment_method__ic__sepa_notice"}
                        <div class="sepa-intro">
                            {$ratepay.translations.wcd_sepa_notice_block}
                        </div>
                    {/block}
                    {block name="ratepay_payment_method__ic__bank_account"}
                        <div class="ratepay-installment_bank-data">
                            {include file="frontend/plugins/payment/ratepay/common/bank_account.tpl"}
                        </div>
                    {/block}
                </div>
            {/block}
        </div>
    {/block}
</div>