<!--
  ~ Copyright (c) 2020 RatePAY GmbH
  ~
  ~ For the full copyright and license information, please view the LICENSE
  ~ file that was distributed with this source code.
  -->

<script type="text/javascript">
    window.rpBankTransferAllowed = "{$ratepay.calculator.rp_debitPayType.rp_paymentType_bankTransfer}" === "1";
    window.rpBankTransferFirstday = "{$ratepay.calculator.rp_debitPayType.rp_paymentType_bankTransfer_firstday}";
    window.rpDirectDebitAllowed = "{$ratepay.calculator.rp_debitPayType.rp_paymentType_directDebit}" === "1";
    window.rpDirectDebitFirstday = "{$ratepay.calculator.rp_debitPayType.rp_paymentType_directDebit_firstday}";
</script>

<div class="installment-calculator">
    <div class="container">
        <div class="rp-container-calculator">
            <div class="row">
                <div class="col-md-12">
                    <hr/>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {$ratepay.translations.rp_calculation_intro_part1} {$ratepay.translations.rp_calculation_intro_part2} {$ratepay.translations.rp_calculation_intro_part3}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading text-center" id="firstInput">
                            <h2>{$ratepay.translations.rp_runtime_title}</h2>
                            {$ratepay.translations.rp_runtime_description}
                        </div>

                        <div class="panel-body">
                            <div class="form-group runtime-select-container">
                                <select id="rp-btn-runtime">
                                    <option></option>
                                    {foreach from=$ratepay.calculator.rp_allowedMonths item=month}
                                        <option value="{$month}">{$month} {$ratepay.translations.rp_monthly_installment_pl}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading text-center" id="secondInput">
                            <h2>{$ratepay.translations.rp_rate_title}</h2>
                            {$ratepay.translations.rp_rate_description}
                        </div>

                        <div class="panel-body">
                            <div class="input-group">
                                <span class="input-group-addon">&euro;</span>
                                <input type="text" id="rp-rate-value" class="form-control" aria-label="Amount" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default rp-btn-rate" type="button" disabled>{$ratepay.translations.rp_calculate_rate}</button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12" id="rpResultContainer"></div>
        </div>

        <div class="row rp-sepa-form">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row rp-row-space rp-sepa-form">
            <div class="col-md-12">
                <table class="rp-sepa-table small">
                    <tr>
                        <td colspan="2">{$ratepay.translations.rp_address}</td>
                    </tr>
                    <tr>
                        <td>{$ratepay.translations.rp_creditor}</td>
                        <td>{$ratepay.translations.rp_creditor_id}</td>
                    </tr>
                    <tr>
                        <td>{$ratepay.translations.rp_mandate}</td>
                        <td>{$ratepay.translations.rp_mandate_ref}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row rp-sepa-form">
            <div class="col-md-12 sepa-intro">
                {$ratepay.translations.wcd_sepa_notice_block}
            </div>
            <div class="col-md-12">
                <input type="hidden" name="rp-payment-type" id="rp-payment-type" />

                {include file="frontend/plugins/payment/ratepay/common/bank_account.tpl"}
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <!-- Switching between payment type direct debit and bank transfer (which requires no sepa form) is only allowed if  -->
                <div class="rp-payment-type-switch" id="rp-switch-payment-type-bank-transfer">
                    <a class="rp-link">{$ratepay.translations.rp_switch_payment_type_bank_transfer}</a>
                </div>
                <div class="rp-payment-type-switch" id="rp-switch-payment-type-direct-debit">
                    <a class="rp-link">{$ratepay.translations.rp_switch_payment_type_direct_debit}</a>
                </div>
            </div>
        </div>
    </div>
</div>
