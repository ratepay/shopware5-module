<!--
  ~ Copyright (c) 2020 Ratepay GmbH
  ~
  ~ For the full copyright and license information, please view the LICENSE
  ~ file that was distributed with this source code.
  -->

{block name="ratepay_payment_method__ic"}
    <div class="installment-calculator">
        <div class="container">
            {block name="ratepay_payment_method__ic__inputs_outer"}
                <div class="rp-container-calculator">
                    {block name="ratepay_payment_method__ic__intro"}
                        <div class="row">
                            <div class="col-md-12">
                                {$ratepay.translations.rp_calculation_intro_part1} {$ratepay.translations.rp_calculation_intro_part2} {$ratepay.translations.rp_calculation_intro_part3}
                            </div>
                        </div>
                    {/block}

                    {block name="ratepay_payment_method__ic__calculator"}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    {block name="ratepay_payment_method__ic__calculator_runtime"}
                                        <div class="panel-heading text-center" id="firstInput">
                                            <h2>{$ratepay.translations.rp_runtime_title}</h2>
                                            {$ratepay.translations.rp_runtime_description}
                                        </div>

                                        <div class="panel-body">
                                            <div class="form-group runtime-select-container">
                                                <select id="rp-btn-runtime">
                                                    {foreach from=$ratepay.calculator.rp_allowedMonths item=month}
                                                        <option value="{$month}">{$month} {$ratepay.translations.rp_monthly_installment_pl}</option>
                                                    {/foreach}
                                                </select>
                                            </div>
                                        </div>
                                    {/block}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    {block name="ratepay_payment_method__ic__calculator_ratevalue"}
                                        <div class="panel-heading text-center" id="secondInput">
                                            <h2>{$ratepay.translations.rp_rate_title}</h2>
                                            {$ratepay.translations.rp_rate_description}
                                        </div>

                                        <div class="panel-body">
                                            <div class="input-group">
                                                <span class="input-group-addon">&euro;</span>
                                                <input type="text" id="rp-rate-value" class="form-control" />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-default rp-btn-rate" type="button" disabled>{$ratepay.translations.rp_calculate_rate}</button>
                                                </span>
                                            </div>
                                        </div>
                                    {/block}
                                </div>
                            </div>
                        </div>
                    {/block}
                </div>
            {/block}

            {block name="ratepay_payment_method__ic__plan_outer"}
                <div class="row">
                    <div class="col-md-12" id="rpResultContainer"></div>
                </div>
            {/block}

            {block name="ratepay_payment_method__ic__sepa_outer"}
                <div class="row rp-sepa-form">
                    {block name="ratepay_payment_method__ic__sepa_notice"}
                        <div class="col-md-12 sepa-intro">
                            {$ratepay.translations.wcd_sepa_notice_block}
                        </div>
                    {/block}
                    {block name="ratepay_payment_method__ic__sepa_notice"}
                        <div class="col-md-12">
                            {include file="frontend/plugins/payment/ratepay/common/bank_account.tpl"}
                        </div>
                    {/block}
                </div>
            {/block}

            {block name="ratepay_payment_method__ic__payment_type_outer"}
                <div class="row payment-type-select-container">
                    <div class="col-md-12">
                        {block name="ratepay_payment_method__ic__payment_type_links"}
                            <div class="rp-payment-type-switch" id="rp-switch-payment-type-bank-transfer">
                                <a class="rp-link">{$ratepay.translations.rp_switch_payment_type_bank_transfer}</a>
                            </div>
                            <div class="rp-payment-type-switch" id="rp-switch-payment-type-direct-debit">
                                <a class="rp-link">{$ratepay.translations.rp_switch_payment_type_direct_debit}</a>
                            </div>
                        {/block}
                    </div>
                </div>
            {/block}
        </div>
    </div>

    <div id="ratepay__installment__message-template" style="display: none!important">
        {include file="frontend/_includes/messages.tpl" type="error" content="<div class='placeholder'></div>" isHidden=true}
    </div>
{/block}
