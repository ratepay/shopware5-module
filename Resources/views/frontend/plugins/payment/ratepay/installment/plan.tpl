{*
Copyright (c) Ratepay GmbH

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*}
<input type="hidden" name="ratepay[installment][payment_type]" id="rp-payment-type" value="{$ratepay.defaults.paymentType}">

{block name="ratepay_payment_method__ic_plan"}
    <table class="ratepay-installment_plan-table">
        <thead>
            <tr>
                <th colspan="2">
                    <span class="heading">{$ratepay.translations.rp_personal_calculation}</span>
                    <span class="calculation-info">{$ratepay.plan.responseText}<br/>{$ratepay.translations.rp_calulation_example}</span>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2">
                    <a class="rp-link" id="rp-show-installment-plan-details">{$ratepay.translations.rp_showInstallmentPlanDetails}<i class="icon--arrow-down"></i></a>
                    <a class="rp-link" id="rp-hide-installment-plan-details">{$ratepay.translations.rp_hideInstallmentPlanDetails}<i class="icon--arrow-up"></i></a>
                </td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.translations.rp_cash_payment_price}
                    <span class="small">{$ratepay.translations.rp_mouseover_cash_payment_price}</span>
                </td>
                <td>{$ratepay.plan.amount} &euro;</td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.translations.rp_service_charge}
                    <span class="small">{$ratepay.translations.rp_mouseover_service_charge}</span>
                </td>
                <td>{$ratepay.plan.serviceCharge} &euro;</td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.translations.rp_effective_rate}
                    <span class="small">{$ratepay.translations.rp_mouseover_effective_rate}</span>
                </td>
                <td>{$ratepay.plan.annualPercentageRate} %</td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.translations.rp_debit_rate}
                    <span class="small">{$ratepay.translations.rp_mouseover_debit_rate}</span>
                </td>
                <td>{$ratepay.plan.interestRate} %</td>
            </tr>
            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.plan.translations.rp_interest_amount}
                    <span class="small">{$ratepay.translations.rp_mouseover_interest_amount}</span>
                </td>
                <td>{$ratepay.plan.interestAmount} &euro;</td>
            </tr>

            <tr class="rp-installment-plan-details separator">
                <td colspan="2"></td>
            </tr>

            <tr class="rp-installment-plan-no-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.plan.numberOfRatesFull} {$ratepay.translations.rp_monthly_installment_pl} {$ratepay.translations.rp_each}
                    <span class="small">{$ratepay.translations.rp_mouseover_duration_month}</span>
                </td>
                <td>{$ratepay.plan.rate} &euro;</td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    1.-{$ratepay.plan.numberOfRatesFull - 1}
                    . {$ratepay.translations.rp_monthly_installment_sg} {$ratepay.translations.rp_each}
                    <span class="small">{$ratepay.translations.rp_mouseover_duration_month}</span>
                </td>
                <td>{$ratepay.plan.rate} &euro;</td>
            </tr>

            <tr class="rp-installment-plan-details">
                <td class="rp-installment-plan-title">
                    {$ratepay.plan.numberOfRatesFull}. {$ratepay.translations.rp_monthly_installment_sg}
                    <span class="small">{$ratepay.translations.rp_mouseover_last_rate}</span>
                </td>
                <td>{$ratepay.plan.lastRate} &euro;</td>
            </tr>

            <tr>
                <td class="rp-installment-plan-title">
                    {$ratepay.translations.rp_total_amount}
                    <span class="small">{$ratepay.translations.rp_mouseover_total_amount}</span>
                </td>
                <td>{$ratepay.plan.totalAmount} &euro;</td>
            </tr>
        </tbody>
    </table>
{/block}
