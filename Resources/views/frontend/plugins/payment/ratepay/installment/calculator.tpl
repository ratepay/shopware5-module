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
                            <div class="form-group">
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
                            <div class="input-group input-group-sm">
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
            <div class="sepa-intro">
                {$ratepay.translations.wcd_sepa_notice_block}
            </div>
            <div class="col-md-12">
                <input type="hidden" name="rp-payment-type" id="rp-payment-type" />

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label">{$ratepay.translations.rp_account_holder}</label>
                    <div class="col-sm-10">
                        <input type="text" id="rp-iban-account-holder" class="form-control disabled" value="{$ratepay.data.customer_name}" disabled /><!-- Show account holder name = billing address firstname and lastname -->
                    </div>
                </div>
                <!-- Account number is only allowed for customers with german billing address. IBAN must be used for all others -->
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label" for="rp-iban-account-number">{$ratepay.translations.rp_iban}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control required" id="rp-iban-account-number" name="ratepay[bank_account][iban]" value="{$ratepay.data.bank_data_iban}" />
                    </div>
                </div>
                <!-- Bank code is only necessary if account number (no iban) is set -->
                <div class="form-group row" id="rp-form-bank-code">
                    <label class="col-sm-2 col-form-label" for="rp-bank-code">{$ratepay.translations.rp_bank_code}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="rp-bank-code" name="ratepay[bank_account][bankCode]" value="{$ratepay.data.bank_data_bankcode}" />
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-2 col-form-label"></label>
                    <div class="col-sm-10">
                        <input type="checkbox" name="ratepay[sepa_agreement]" id="rp-sepa-aggreement" class="required" required="required" />
                        <label for="rp-sepa-aggreement" class="rp-label-sepa-agreement">{s namespace="frontend/plugins/payment/ratepay" name="sepaAuthorize"}{/s}</label>
                    </div>
                </div>
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
